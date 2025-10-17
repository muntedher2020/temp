<div>
    {{-- Main Card --}}
    <div class="card">
        {{-- Card Header --}}
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="d-flex align-items-center gap-2">
                        <span class="text-muted d-flex align-items-center">
                            <i class="mdi mdi-cog-outline fs-4"></i>
                            <span class="ms-1">الإدارة</span>
                        </span>
                        <i class="mdi mdi-chevron-left text-primary"></i>
                        <span class="fw-bold text-primary d-flex align-items-center">
                            <i class="mdi mdi-folder-multiple-outline me-1 fs-4"></i>
                            <span class="ms-1">المجموعات الأساسية</span>
                        </span>
                    </h4>
                    <p class="mb-0">إدارة وتنظيم المجموعات الأساسية للنظام وتحديد ترتيب عرضها في القائمة الرئيسية</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    {{-- Search Input --}}
                    <div class="position-relative">
                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
                            <i class="mdi mdi-magnify"></i>
                        </span>
                        <input type="text" wire:model="search" class="form-control form-control-solid w-250px ps-15"
                            placeholder="البحث في المجموعات..." />
                    </div>

                    {{-- Add Button --}}
                    <button type="button" wire:click="create" class="btn btn-primary">
                        <span class="svg-icon svg-icon-2">
                            <i class="mdi mdi-plus"></i>
                        </span>
                        إضافة مجموعة
                    </button>
                </div>
            </div>
        </div>

        {{-- Additional Controls --}}
        <div class="card-header border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    {{-- Status Filter --}}
                    <div>
                        <label class="form-label fw-bold fs-7 text-muted mb-1">تصفية حسب الحالة:</label>
                        <select wire:model="statusFilter" class="form-select form-select-sm form-select-solid w-150px">
                            <option value="">جميع الحالات</option>
                            <option value="1">مفعل</option>
                            <option value="0">غير مفعل</option>
                        </select>
                    </div>

                    {{-- Per Page --}}
                    <div>
                        <label class="form-label fw-bold fs-7 text-muted mb-1">عدد العناصر:</label>
                        <select wire:model="perPage" class="form-select form-select-sm form-select-solid w-100px">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    {{-- Sync Menu Button --}}
                    <button type="button" wire:click="syncMenu" class="btn btn-light-success btn-sm">
                        <span class="svg-icon svg-icon-2">
                            <i class="mdi mdi-sync"></i>
                        </span>
                        تحديث القائمة
                    </button>

                    {{-- Rescan Missing Modules Button --}}
                    <button type="button" wire:click="rescanAndRestoreMissingModules"
                        class="btn btn-light-info btn-sm">
                        <span class="svg-icon svg-icon-2">
                            <i class="mdi mdi-magnify-scan"></i>
                        </span>
                        فحص الوحدات المفقودة
                    </button>
                </div>
            </div>
        </div>

        {{-- Card Body --}}
        <div class="card-body py-4">
            {{-- Mobile Responsive Cards for Small Screens --}}
            <div class="d-block d-md-none">
                @forelse($basicGroups as $group)
                    <div class="card mb-4 {{ $group->trashed() ? 'border-warning' : '' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-light-primary text-primary">
                                        {!! $group->getIconPreview() !!}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $group->name_ar }}</h6>
                                    <small class="text-muted">{{ $group->name_en }}</small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light btn-active-light-primary" type="button"
                                        data-bs-toggle="dropdown">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @if ($group->trashed())
                                            <li><a class="dropdown-item" href="#"
                                                    wire:click="restore({{ $group->id }})">
                                                    <i class="mdi mdi-restore text-success"></i> استعادة
                                                </a></li>
                                            <li><a class="dropdown-item text-danger" href="#"
                                                    wire:click="confirmDelete({{ $group->id }})">
                                                    <i class="mdi mdi-delete-forever"></i> حذف نهائي
                                                </a></li>
                                        @else
                                            <li><a class="dropdown-item" href="#"
                                                    wire:click="edit({{ $group->id }})">
                                                    <i class="mdi mdi-pencil text-primary"></i> تعديل
                                                </a></li>
                                            <li><a class="dropdown-item" href="#"
                                                    wire:click="toggleStatus({{ $group->id }})">
                                                    <i
                                                        class="mdi mdi-{{ $group->status ? 'eye-off' : 'eye' }} text-warning"></i>
                                                    {{ $group->status ? 'إلغاء تفعيل' : 'تفعيل' }}
                                                </a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item text-danger" href="#"
                                                    wire:click="confirmDelete({{ $group->id }})">
                                                    <i class="mdi mdi-delete"></i> حذف
                                                </a></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">الترتيب</small>
                                    <span class="badge badge-light">{{ $group->sort_order }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">الحالة</small>
                                    @if ($group->trashed())
                                        <span class="badge badge-light-danger">محذوف</span>
                                    @else
                                        <span class="badge badge-light-{{ $group->status ? 'success' : 'danger' }}">
                                            {{ $group->status_text }}
                                        </span>
                                    @endif
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">الإنشاء</small>
                                    <span class="text-dark">{{ $group->created_at->format('m/d') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center py-10">
                            <i class="mdi mdi-folder-open fs-4x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد مجموعات أساسية</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Desktop Table for Large Screens --}}
            <div class="d-none d-md-block">
                <div style="min-height: 400px;">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_basic_groups_table">
                        <thead>
                            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                                <th class="min-w-200px">المعاينة</th>
                                <th class="min-w-120px">الاسم الإنجليزي</th>
                                <th class="min-w-120px">الاسم العربي</th>
                                <th class="min-w-80px">الترتيب</th>
                                <th class="min-w-80px">الحالة</th>
                                <th class="min-w-100px">تاريخ الإنشاء</th>
                                <th class="text-end min-w-120px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-bold">
                            @forelse($basicGroups as $group)
                                <tr class="{{ $group->trashed() ? 'table-warning' : '' }}">
                                    {{-- Preview --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-3">
                                                <span class="symbol-label bg-light-primary text-primary">
                                                    {!! $group->getIconPreview() !!}
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <a href="#"
                                                    class="text-gray-800 text-hover-primary mb-1">{{ $group->name_ar }}</a>
                                                <span
                                                    class="text-muted fw-bold d-block fs-7">{{ $group->name_en }}</span>
                                                @if ($group->icon)
                                                    <code class="fs-8 text-muted">{{ $group->icon }}</code>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- English Name --}}
                                    <td>
                                        <span class="text-dark fw-bold">{{ $group->name_en }}</span>
                                        @if ($group->description_en)
                                            <br><small
                                                class="text-muted">{{ Str::limit($group->description_en, 40) }}</small>
                                        @endif
                                    </td>

                                    {{-- Arabic Name --}}
                                    <td>
                                        <span class="text-dark fw-bold">{{ $group->name_ar }}</span>
                                        @if ($group->description_ar)
                                            <br><small
                                                class="text-muted">{{ Str::limit($group->description_ar, 40) }}</small>
                                        @endif
                                    </td>

                                    {{-- Sort Order --}}
                                    <td>
                                        <span class="text-dark fw-bold">{{ $group->sort_order }}</span>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        @if ($group->trashed())
                                            <span class="badge bg-danger text-white fs-7">محذوف</span>
                                        @else
                                            <span
                                                class="badge bg-{{ $group->status ? 'success' : 'danger' }} text-white fs-7">
                                                {{ $group->status_text }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Created Date --}}
                                    <td>
                                        <span class="text-dark">{{ $group->created_at->format('Y-m-d') }}</span>
                                        <br><small class="text-muted">{{ $group->created_at->format('H:i') }}</small>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            @if ($group->trashed())
                                                <button wire:click="restore({{ $group->id }})"
                                                    class="btn btn-icon btn-bg-light btn-active-color-success btn-sm me-1"
                                                    title="استعادة">
                                                    <i class="mdi mdi-restore fs-4"></i>
                                                </button>
                                                <button wire:click="confirmDelete({{ $group->id }})"
                                                    class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
                                                    title="حذف نهائي">
                                                    <i class="mdi mdi-delete-forever fs-4"></i>
                                                </button>
                                            @else
                                                <button wire:click="edit({{ $group->id }})"
                                                    class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
                                                    title="تعديل">
                                                    <i class="mdi mdi-pencil fs-4"></i>
                                                </button>
                                                <button wire:click="toggleStatus({{ $group->id }})"
                                                    class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1"
                                                    title="{{ $group->status ? 'إلغاء تفعيل' : 'تفعيل' }}">
                                                    <i
                                                        class="mdi mdi-{{ $group->status ? 'eye-off' : 'eye' }} fs-4"></i>
                                                </button>
                                                <button wire:click="confirmDelete({{ $group->id }})"
                                                    class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
                                                    title="حذف">
                                                    <i class="mdi mdi-delete fs-4"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-folder-open fs-4x text-muted mb-3"></i>
                                            <span class="text-muted">لا توجد مجموعات أساسية</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="pagination-wrapper">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted">
                        @if ($basicGroups->count() > 0)
                            عرض {{ $basicGroups->firstItem() }} إلى {{ $basicGroups->lastItem() }}
                            من أصل {{ $basicGroups->total() }} مجموعة
                        @else
                            لا توجد نتائج
                        @endif
                    </div>
                    <div>
                        {{ $basicGroups->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $isEditing ? 'تعديل المجموعة الأساسية' : 'إضافة مجموعة أساسية جديدة' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="row g-9 mb-8">
                                {{-- English Name --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-bold mb-2">الاسم الإنجليزي</label>
                                    <input type="text" wire:model="name_en"
                                        class="form-control @error('name_en') is-invalid @enderror"
                                        placeholder="Enter English name" />
                                    @error('name_en')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Arabic Name --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-bold mb-2">الاسم العربي</label>
                                    <input type="text" wire:model="name_ar"
                                        class="form-control @error('name_ar') is-invalid @enderror"
                                        placeholder="أدخل الاسم العربي" />
                                    @error('name_ar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                {{-- Icon --}}
                                <div class="col-md-8 fv-row">
                                    <label class="required fs-6 fw-bold mb-2">الأيقونة</label>
                                    <div class="input-group">
                                        <input type="text" wire:model="icon"
                                            class="form-control @error('icon') is-invalid @enderror"
                                            placeholder="mdi mdi-folder-outline" />
                                        <button type="button" class="btn btn-outline-secondary"
                                            wire:click="openIconPicker">
                                            اختيار أيقونة
                                        </button>
                                    </div>
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Icon Preview --}}
                                <div class="col-md-4 fv-row">
                                    <label class="fs-6 fw-bold mb-2">معاينة الأيقونة</label>
                                    <div class="border rounded p-3 bg-light-primary" style="min-height: 120px;">
                                        <div class="text-center">
                                            @if ($iconPreview)
                                                <div class="mb-3">
                                                    <i class="{{ $iconPreview }} fs-3x text-primary"></i>
                                                </div>
                                                <div class="text-dark fw-bold mb-2">
                                                    {{ $name_ar ?: 'اسم الصفحة' }}
                                                </div>
                                                <code class="fs-7 text-muted d-block">{{ $iconPreview }}</code>
                                            @else
                                                <div
                                                    class="d-flex flex-column align-items-center justify-content-center h-100">
                                                    <i class="mdi mdi-image-outline fs-3x text-muted mb-2"></i>
                                                    <span class="text-muted fs-7">اختر أيقونة لمعاينتها</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                {{-- English Description --}}
                                <div class="col-md-6 fv-row">
                                    <label class="fs-6 fw-bold mb-2">الوصف الإنجليزي</label>
                                    <textarea wire:model="description_en" class="form-control @error('description_en') is-invalid @enderror"
                                        rows="3" placeholder="Enter English description"></textarea>
                                    @error('description_en')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Arabic Description --}}
                                <div class="col-md-6 fv-row">
                                    <label class="fs-6 fw-bold mb-2">الوصف العربي</label>
                                    <textarea wire:model="description_ar" class="form-control @error('description_ar') is-invalid @enderror"
                                        rows="3" placeholder="أدخل الوصف العربي"></textarea>
                                    @error('description_ar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                {{-- Sort Order --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-bold mb-2">ترتيب العرض</label>
                                    <div class="input-group">
                                        <input type="number" wire:model="sort_order"
                                            class="form-control @error('sort_order') is-invalid @enderror"
                                            placeholder="أدخل رقم ترتيب العرض" min="0" />
                                        <button type="button" class="btn btn-outline-secondary"
                                            wire:click="suggestSortOrder">
                                            اقتراح رقم متاح
                                        </button>
                                    </div>
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        رقم ترتيب العرض في القائمة (يجب أن يكون فريداً)
                                    </small>
                                </div>

                                {{-- Type --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-bold mb-2">نوع العنصر</label>
                                    <select wire:model="type" class="form-select @error('type') is-invalid @enderror">
                                        <option value="group">مجموعة (تحتوي على وحدات فرعية)</option>
                                        <option value="item">عنصر مستقل (لا يحتوي على وحدات فرعية)</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        المجموعة تحتوي على وحدات فرعية، العنصر المستقل وحدة بذاتها
                                    </small>
                                </div>
                            </div>

                            <!-- Route Field (shown only when type is 'item') -->
                            @if ($type === 'item')
                                <div class="row g-9 mb-8">
                                    <div class="col-md-12 fv-row">
                                        <label class="required fs-6 fw-bold mb-2">مسار الوحدة</label>
                                        <input type="text" wire:model="route"
                                            class="form-control @error('route') is-invalid @enderror"
                                            placeholder="أدخل مسار الوحدة (مثل: Employees)" />
                                        @error('route')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            المسار المستخدم للوصول للوحدة (مطلوب للعناصر المستقلة فقط)
                                        </small>
                                    </div>
                                </div>
                            @endif

                            <div class="row g-9 mb-8">
                                {{-- Status --}}
                                <div class="col-md-6 fv-row">
                                    <label class="fs-6 fw-bold mb-2">الحالة</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model="status"
                                            id="status">
                                        <label class="form-check-label" for="status">
                                            {{ $status ? 'مفعل' : 'غير مفعل' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">
                                {{ $isEditing ? 'تحديث' : 'حفظ' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
                                            <div class="card card-flush h-100 cursor-pointer"
                                                wire:click="selectIcon('{{ $iconClass }}')"
                                                style="transition: all 0.3s;"
                                                onmouseover="this.style.transform='scale(1.05)'"
                                                onmouseout="this.style.transform='scale(1)'">
                                                <div class="card-body text-center py-4">
                                                    <i class="{{ $iconClass }} fs-2x text-primary mb-2"></i>
                                                    <div class="text-muted fs-8 text-truncate">{{ $iconClass }}
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

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal && $selectedItem)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="mdi mdi-alert-circle-outline fs-4x text-warning mb-4"></i>
                            <p>
                                @if ($selectedItem->trashed())
                                    هل أنت متأكد من أنك تريد حذف المجموعة الأساسية
                                    "<strong>{{ $selectedItem->name_ar }}</strong>" نهائياً؟
                                    <br><span class="text-danger fw-bold">لا يمكن التراجع عن هذا الإجراء!</span>
                                @else
                                    هل أنت متأكد من أنك تريد حذف المجموعة الأساسية
                                    "<strong>{{ $selectedItem->name_ar }}</strong>"؟
                                    <br><span class="text-muted">يمكنك استعادتها لاحقاً من سلة المحذوفات</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeModal">إلغاء</button>
                        <button type="button" class="btn btn-danger" wire:click="delete">
                            {{ $selectedItem->trashed() ? 'حذف نهائي' : 'حذف' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
