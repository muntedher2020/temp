<div>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="mdi mdi-view-dashboard-edit text-primary me-2"></i>
                        مصمم الداشبورد الديناميكي
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>مصمم الداشبورد الديناميكي:</strong> قم ببناء وتخصيص محتوى لوحة التحكم بإضافة عناصر
                        مختلفة مثل الجداول والإحصائيات والمخططات البيانية من الوحدات المتاحة في النظام.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- عرض العناصر الحالية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-widgets-outline me-2 text-primary"></i>
                        عناصر الداشبورد ({{ count($widgets) }} عنصر)
                    </h5>
                    <div class="d-flex gap-2">
                        @if (count($widgets) > 0)
                            <a href="{{ route('Dashboard') }}" class="btn btn-success btn-sm" target="_blank">
                                <i class="mdi mdi-eye me-1"></i>
                                معاينة الداشبورد
                            </a>
                        @endif
                        <button type="button" class="btn btn-primary btn-sm" wire:click="$set('showAddWidget', true)">
                            <i class="mdi mdi-plus me-1"></i>
                            إضافة عنصر جديد
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if (count($widgets) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60" class="text-center">الترتيب</th>
                                        <th>العنوان</th>
                                        <th width="120" class="text-center">النوع</th>
                                        <th width="150">الوحدة</th>
                                        <th width="120" class="text-center">الحالة</th>
                                        <th width="140" class="text-center">التاريخ</th>
                                        <th width="200" class="text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($widgets as $index => $widget)
                                        <tr
                                            class="{{ !($widget['active'] ?? true) ? 'table-secondary opacity-75' : '' }}">
                                            <td class="text-center">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <div class="position-relative">
                                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                                        @if (!($widget['active'] ?? true))
                                                            <span
                                                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                                                title="معطل">
                                                                <i class="mdi mdi-pause" style="font-size: 8px;"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        @if ($index > 0)
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm"
                                                                wire:click="moveWidgetUp({{ $index }})"
                                                                title="نقل للأعلى">
                                                                <i class="mdi mdi-arrow-up"></i>
                                                            </button>
                                                        @endif
                                                        @if ($index < count($widgets) - 1)
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm"
                                                                wire:click="moveWidgetDown({{ $index }})"
                                                                title="نقل للأسفل">
                                                                <i class="mdi mdi-arrow-down"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="position-relative me-2">
                                                        @if (isset($widget['type']) && $widget['type'] === 'stat')
                                                            @php
                                                                $widgetColor = $widget['color'] ?? 'primary';
                                                                $isCustomColor = str_starts_with($widgetColor, '#');
                                                            @endphp
                                                            <i class="mdi {{ $widget['icon'] ?? 'mdi-chart-line' }} @if ($isCustomColor) text-dark @else text-{{ $widgetColor }} @endif fs-5 {{ !($widget['active'] ?? true) ? 'opacity-50' : '' }}"
                                                                @if ($isCustomColor) style="color: {{ $widgetColor }} !important;" @endif></i>
                                                        @elseif(isset($widget['type']) && $widget['type'] === 'table')
                                                            <i
                                                                class="mdi mdi-table text-info fs-5 {{ !($widget['active'] ?? true) ? 'opacity-50' : '' }}"></i>
                                                        @elseif(isset($widget['type']) && $widget['type'] === 'chart')
                                                            <i
                                                                class="mdi mdi-chart-bar text-success fs-5 {{ !($widget['active'] ?? true) ? 'opacity-50' : '' }}"></i>
                                                        @endif

                                                        @if (!($widget['active'] ?? true))
                                                            <span
                                                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                                                style="font-size: 8px; padding: 2px 4px;">
                                                                <i class="mdi mdi-pause" style="font-size: 6px;"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div
                                                        class="{{ !($widget['active'] ?? true) ? 'opacity-75' : '' }}">
                                                        <div class="fw-bold">{{ $widget['title'] ?? 'بلا عنوان' }}
                                                        </div>
                                                        @if (isset($widget['label']) && $widget['label'] !== $widget['title'])
                                                            <small class="text-muted">{{ $widget['label'] }}</small>
                                                        @endif
                                                        @if (!($widget['active'] ?? true))
                                                            <small class="text-danger d-block">
                                                                <i class="mdi mdi-information-outline me-1"></i>
                                                                هذا العنصر معطل ولن يظهر في الداشبورد
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if (isset($widget['type']))
                                                    @if ($widget['type'] === 'stat')
                                                        <span class="badge bg-primary">إحصائية</span>
                                                    @elseif($widget['type'] === 'table')
                                                        <span class="badge bg-info">جدول</span>
                                                    @elseif($widget['type'] === 'chart')
                                                        <span class="badge bg-success">مخطط</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-light text-dark">{{ $widget['module'] ?? 'غير محدد' }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($widget['active'] ?? true)
                                                    <span class="badge bg-success">مفعل</span>
                                                @else
                                                    <span class="badge bg-secondary">معطل</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">
                                                    {{ isset($widget['created_at']) ? \Carbon\Carbon::parse($widget['created_at'])->format('Y-m-d') : 'غير محدد' }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        wire:click="editWidget('{{ $widget['id'] ?? '' }}')"
                                                        title="تعديل">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-outline-{{ $widget['active'] ?? true ? 'warning' : 'success' }}"
                                                        wire:click="toggleWidgetStatus('{{ $widget['id'] ?? '' }}')"
                                                        title="{{ $widget['active'] ?? true ? 'إلغاء التفعيل' : 'تفعيل' }}">
                                                        <i
                                                            class="mdi mdi-{{ $widget['active'] ?? true ? 'eye-off' : 'eye' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        wire:click="removeWidget('{{ $widget['id'] ?? '' }}')"
                                                        title="حذف">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="mdi mdi-widgets-outline display-1 text-muted"></i>
                            <h5 class="text-muted mt-3">لا توجد عناصر في الداشبورد</h5>
                            <p class="text-muted">ابدأ بإضافة عنصر جديد لتخصيص لوحة التحكم</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل عنصر -->
    @if ($showAddWidget)
        <div class="row">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">
                                <i class="mdi mdi-{{ $editingWidget ? 'pencil' : 'plus-circle' }} me-2"></i>
                                {{ $editingWidget ? 'تعديل عنصر الداشبورد' : 'إضافة عنصر جديد للداشبورد' }}
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-light"
                                wire:click="$set('showAddWidget', false)">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- نظام التبويبات -->
                        <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'basic' ? 'active' : '' }}"
                                    wire:click="$set('activeTab', 'basic')" type="button">
                                    <i class="mdi mdi-cog me-1"></i>
                                    الإعدادات الأساسية
                                </button>
                            </li>
                            @if ($selectedModule)
                                <li class="nav-item">
                                    <button class="nav-link {{ $activeTab === 'config' ? 'active' : '' }}"
                                        wire:click="$set('activeTab', 'config')" type="button">
                                        <i class="mdi mdi-tune me-1"></i>
                                        إعدادات العنصر
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link {{ $activeTab === 'advanced' ? 'active' : '' }}"
                                        wire:click="$set('activeTab', 'advanced')" type="button">
                                        <i class="mdi mdi-settings me-1"></i>
                                        إعدادات متقدمة
                                    </button>
                                </li>
                            @endif
                        </ul>

                        <!-- محتوى التبويبات -->
                        <div class="tab-content">
                            <!-- التبويب الأساسي -->
                            @if ($activeTab === 'basic')
                                <div class="tab-pane fade show active">
                                    <!-- اختيار نوع العنصر -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label fw-bold text-primary">
                                                <i class="mdi mdi-shape me-1"></i>
                                                نوع العنصر
                                            </label>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <div class="card border-primary {{ $widgetType === 'stat' ? 'bg-light-primary border-2' : '' }} cursor-pointer"
                                                        wire:click="$set('widgetType', 'stat')">
                                                        <div class="card-body text-center">
                                                            <input class="form-check-input d-none" type="radio"
                                                                wire:model="widgetType" value="stat"
                                                                id="widgetTypeStat">
                                                            <i
                                                                class="mdi mdi-chart-line display-6 text-primary d-block mb-2"></i>
                                                            <strong>إحصائية</strong>
                                                            <small class="d-block text-muted">عرض رقم أو إحصائية
                                                                سريعة</small>
                                                            @if ($widgetType === 'stat')
                                                                <i
                                                                    class="mdi mdi-check-circle text-primary position-absolute top-0 end-0 m-2"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border-info {{ $widgetType === 'table' ? 'bg-light-info border-2' : '' }} cursor-pointer"
                                                        wire:click="$set('widgetType', 'table')">
                                                        <div class="card-body text-center">
                                                            <input class="form-check-input d-none" type="radio"
                                                                wire:model="widgetType" value="table"
                                                                id="widgetTypeTable">
                                                            <i
                                                                class="mdi mdi-table display-6 text-info d-block mb-2"></i>
                                                            <strong>جدول</strong>
                                                            <small class="d-block text-muted">عرض البيانات في جدول
                                                                منظم</small>
                                                            @if ($widgetType === 'table')
                                                                <i
                                                                    class="mdi mdi-check-circle text-info position-absolute top-0 end-0 m-2"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card border-success {{ $widgetType === 'chart' ? 'bg-light-success border-2' : '' }} cursor-pointer"
                                                        wire:click="$set('widgetType', 'chart')">
                                                        <div class="card-body text-center">
                                                            <input class="form-check-input d-none" type="radio"
                                                                wire:model="widgetType" value="chart"
                                                                id="widgetTypeChart">
                                                            <i
                                                                class="mdi mdi-chart-bar display-6 text-success d-block mb-2"></i>
                                                            <strong>مخطط بياني</strong>
                                                            <small class="d-block text-muted">رسم بياني تفاعلي
                                                                ومقارنات</small>
                                                            @if ($widgetType === 'chart')
                                                                <i
                                                                    class="mdi mdi-check-circle text-success position-absolute top-0 end-0 m-2"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- الإعدادات الأساسية -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">عنوان العنصر <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" wire:model="widgetTitle"
                                                placeholder="مثال: آخر المستخدمين المسجلين">
                                            @error('widgetTitle')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">اختيار الوحدة <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" wire:model="selectedModule">
                                                <option value="">-- اختر الوحدة --</option>
                                                @foreach ($availableModules as $module)
                                                    <option value="{{ $module['name'] }}">{{ $module['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('selectedModule')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    @if ($selectedModule)
                                        <div class="alert alert-info">
                                            <i class="mdi mdi-information me-2"></i>
                                            تم تحديد الوحدة: <strong>{{ $selectedModule }}</strong>.
                                            انتقل إلى تبويب "إعدادات العنصر" لتخصيص المزيد من الخيارات.
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- تبويب إعدادات العنصر -->
                            @if ($activeTab === 'config' && $selectedModule)
                                <div class="tab-pane fade show active">
                                    @if ($widgetType === 'stat')
                                        @include('livewire.dashboard-builder.partials.stat-config')
                                    @elseif($widgetType === 'table')
                                        @include('livewire.dashboard-builder.partials.table-config')
                                    @elseif($widgetType === 'chart')
                                        @include('livewire.dashboard-builder.partials.chart-config')
                                    @endif
                                </div>
                            @endif

                            <!-- تبويب الإعدادات المتقدمة -->
                            @if ($activeTab === 'advanced' && $selectedModule)
                                <div class="tab-pane fade show active">
                                    @include('livewire.dashboard-builder.partials.advanced-config')
                                </div>
                            @endif
                        </div>

                        <!-- أزرار الحفظ -->
                        <div class="border-top pt-3 mt-4">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="$set('showAddWidget', false)">
                                    <i class="mdi mdi-close me-1"></i>
                                    إلغاء
                                </button>
                                <button type="button" class="btn btn-primary" wire:click="addWidget"
                                    @if (!$selectedModule || !$widgetTitle) disabled @endif>
                                    <i class="mdi mdi-{{ $editingWidget ? 'content-save' : 'plus' }} me-1"></i>
                                    {{ $editingWidget ? 'حفظ التعديلات' : 'إضافة العنصر' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- نافذة تأكيد حذف عنصر -->
    @if ($showDeleteModal)
        <div class="modal modal-alert fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0, 0, 0, 0.5);" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" wire:click="closeDeleteModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4 text-center">
                            <i class="mdi mdi-trash-can-outline display-1 text-danger mb-4"></i>
                            <h4 class="mb-2">هل أنت متأكد من حذف هذا العنصر؟</h4>
                            <p class="text-muted">لن تتمكن من استرجاع هذا العنصر بعد الحذف!</p>
                            @if ($widgetToDelete)
                                @php
                                    $widget = collect($widgets)->firstWhere('id', $widgetToDelete);
                                @endphp
                                @if ($widget)
                                    <div class="alert alert-warning mt-3">
                                        <strong>العنصر المراد حذفه:</strong> {{ $widget['title'] }}
                                    </div>
                                @endif
                            @endif
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-danger me-2" wire:click="confirmDeleteWidget">
                                <i class="mdi mdi-delete me-1"></i>
                                نعم، احذف العنصر
                            </button>
                            <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                                <i class="mdi mdi-close me-1"></i>
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- نافذة اختيار الأيقونات -->
    <div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="iconPickerModalLabel">
                        <i class="mdi mdi-palette me-2"></i>
                        اختيار أيقونة من مكتبة MDI
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- شريط البحث -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="iconSearch"
                                placeholder="ابحث عن أيقونة... (مثال: user, chart, home)">
                            <div class="form-text">
                                <small>💡 نصائح: جرب البحث عن "chart" للمخططات، "user" للمستخدمين، "file"
                                    للملفات</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="iconCategory">
                                <option value="">جميع الفئات (أكثر من 150 أيقونة)</option>
                                <option value="account">👥 حسابات ومستخدمين (20 أيقونة)</option>
                                <option value="chart">📊 مخططات وإحصائيات (20 أيقونة)</option>
                                <option value="file">📁 ملفات ومستندات (20 أيقونة)</option>
                                <option value="calendar">📅 تواريخ وأوقات (20 أيقونة)</option>
                                <option value="shopping">🛒 تسوق ومبيعات (20 أيقونة)</option>
                                <option value="school">🎓 تعليم وتدريب (20 أيقونة)</option>
                                <option value="database">💾 قواعد بيانات (20 أيقونة)</option>
                                <option value="home">🏠 منزل ومكتب (20 أيقونة)</option>
                            </select>
                        </div>
                    </div>

                    <!-- أزرار سريعة للأيقونات الشائعة -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">⭐ أيقونات شائعة:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="quickSelectIcon('home')">
                                <i class="mdi mdi-home me-1"></i> الرئيسية
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="quickSelectIcon('chart-line')">
                                <i class="mdi mdi-chart-line me-1"></i> مخطط
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="quickSelectIcon('account-group')">
                                <i class="mdi mdi-account-group me-1"></i> مستخدمين
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="quickSelectIcon('database')">
                                <i class="mdi mdi-database me-1"></i> بيانات
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="quickSelectIcon('calendar')">
                                <i class="mdi mdi-calendar me-1"></i> تقويم
                            </button>
                        </div>
                    </div>

                    <!-- شبكة الأيقونات -->
                    <div class="row" id="iconGrid">
                        <!-- سيتم ملؤها بـ JavaScript -->
                    </div>

                    <!-- معلومات الأيقونة المحددة -->
                    <div class="alert alert-info mt-3" id="selectedIconInfo" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-information me-2"></i>
                            <div>
                                <strong>الأيقونة المحددة:</strong>
                                <span id="selectedIconName"></span>
                                <br>
                                <small class="text-muted">انقر على "اختيار" لاستخدام هذه الأيقونة</small>
                            </div>
                            <div class="ms-auto">
                                <span id="selectedIconPreview" class="fs-1"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="selectIconBtn" disabled
                        onclick="selectIcon()">
                        <i class="mdi mdi-check me-1"></i>
                        اختيار هذه الأيقونة
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
