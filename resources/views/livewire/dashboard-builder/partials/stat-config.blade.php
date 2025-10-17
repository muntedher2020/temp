<div class="card border-primary">
    <div class="card-header bg-light">
        <h6 class="mb-0 text-primary">
            <i class="mdi mdi-chart-line me-2"></i>
            إعدادات الإحصائية
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-semibold">نوع الإحصائية <span class="text-danger">*</span></label>
                <select class="form-select" wire:model="statType">
                    <option value="count">عدد السجلات</option>
                    <option value="sum">مجموع</option>
                    <option value="avg">متوسط</option>
                    <option value="max">أقصى قيمة</option>
                    <option value="min">أدنى قيمة</option>
                </select>
                <small class="text-muted">حدد نوع العملية الحسابية للإحصائية</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الحقل المستهدف</label>
                <select class="form-select" wire:model="statField" @if($statType === 'count') disabled @endif>
                    <option value="">-- اختياري للعدد --</option>
                    @foreach($numericColumns as $column)
                        <option value="{{ $column }}">{{ ucwords(str_replace('_', ' ', $column)) }}</option>
                    @endforeach
                </select>
                <small class="text-muted">
                    @if($statType === 'count')
                        غير مطلوب لعد السجلات
                    @else
                        اختر الحقل الرقمي للعملية الحسابية
                    @endif
                </small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">التسمية التوضيحية</label>
                <input type="text" class="form-control" wire:model="statLabel" placeholder="مثال: إجمالي المبيعات">
                <small class="text-muted">النص الذي سيظهر تحت الرقم في الإحصائية</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الأيقونة</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="mdi {{ $statIcon }} text-primary"></i>
                    </span>
                    <input type="text" class="form-control" wire:model="statIcon" placeholder="mdi-account-group" id="statIconInput">
                    <button type="button" class="btn btn-outline-primary" onclick="openIconPicker(document.getElementById('statIconInput'))">
                        <i class="mdi mdi-palette"></i>
                        اختر أيقونة
                    </button>
                </div>
                <small class="text-muted">
                    يمكنك كتابة اسم الأيقونة مباشرة أو اختيارها من النافذة
                    <a href="https://pictogrammers.com/library/mdi/" target="_blank" class="text-primary">
                        <i class="mdi mdi-open-in-new"></i>
                        تصفح المكتبة الكاملة
                    </a>
                </small>

                <!-- Quick Icon Selection -->
                <div class="mt-2">
                    <label class="form-label small">أيقونات شائعة:</label>
                    <div class="d-flex flex-wrap gap-2">
                        @php
                            $popularIcons = [
                                'mdi-account-group' => 'مستخدمين',
                                'mdi-chart-line' => 'مخطط',
                                'mdi-cash' => 'نقود',
                                'mdi-file-document' => 'ملفات',
                                'mdi-shopping' => 'تسوق',
                                'mdi-calendar' => 'تاريخ',
                                'mdi-star' => 'نجمة',
                                'mdi-database' => 'قاعدة بيانات',
                                'mdi-trending-up' => 'ارتفاع',
                                'mdi-eye' => 'مشاهدات',
                                'mdi-school' => 'تعليم',
                                'mdi-certificate' => 'شهادة',
                                'mdi-book-open' => 'كتاب',
                                'mdi-laptop' => 'حاسوب'
                            ];
                        @endphp
                        @foreach($popularIcons as $iconClass => $iconLabel)
                            <button type="button"
                                    class="btn btn-sm {{ $statIcon === $iconClass ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    wire:click="$set('statIcon', '{{ $iconClass }}')"
                                    title="{{ $iconLabel }}">
                                <i class="mdi {{ $iconClass }}"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">لون الخلفية</label>
                <select class="form-select" wire:model="statColor" id="colorSelect">
                    <option value="primary">🔵 أزرق (Primary)</option>
                    <option value="success">🟢 أخضر (Success)</option>
                    <option value="warning">🟡 أصفر (Warning)</option>
                    <option value="danger">🔴 أحمر (Danger)</option>
                    <option value="info">🔵 فيروزي (Info)</option>
                    <option value="secondary">⚫ رمادي (Secondary)</option>
                    <option value="dark">⚫ أسود (Dark)</option>
                    <option value="custom">🎨 لون مخصص</option>
                </select>

                <!-- حقل اللون المخصص -->
                @if($statColor === 'custom')
                    <div class="mt-2">
                        <label class="form-label small">اختر اللون المخصص:</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" wire:model="customColor" id="customColorPicker" value="{{ $customColor ?? '#696CFF' }}">
                            <input type="text" class="form-control" wire:model="customColor" placeholder="#696CFF" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <small class="text-muted">يمكنك كتابة الكود السادس عشري أو استخدام أداة اختيار اللون</small>
                    </div>
                @endif

                <!-- ألوان سريعة مخصصة -->
                <div class="mt-2">
                    <label class="form-label small">ألوان سريعة:</label>
                    <div class="d-flex flex-wrap gap-1">
                        @php
                            $quickColors = [
                                '#696CFF' => 'بنفسجي',
                                '#FF6B6B' => 'أحمر فاتح',
                                '#4ECDC4' => 'تركوازي',
                                '#45B7D1' => 'أزرق فاتح',
                                '#96CEB4' => 'أخضر فاتح',
                                '#FFEAA7' => 'أصفر فاتح',
                                '#DDA0DD' => 'وردي',
                                '#98D8C8' => 'نعناعي',
                                '#FF7675' => 'مرجاني',
                                '#74B9FF' => 'أزرق سماوي',
                                '#A29BFE' => 'بنفسجي فاتح',
                                '#FD79A8' => 'وردي فاتح'
                            ];
                        @endphp
                        @foreach($quickColors as $colorCode => $colorName)
                            <button type="button"
                                    class="btn btn-sm border-0 rounded-circle position-relative"
                                    style="width: 30px; height: 30px; background-color: {{ $colorCode }};"
                                    wire:click="setCustomColorQuick('{{ $colorCode }}')"
                                    title="{{ $colorName }}">
                                @if($statColor === 'custom' && $customColor === $colorCode)
                                    <i class="mdi mdi-check position-absolute top-50 start-50 translate-middle text-white" style="font-size: 12px;"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-1">انقر على أي لون للاختيار السريع</small>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">معاينة</label>
                @php
                    $isCustomColor = $statColor === 'custom' || str_starts_with($statColor, '#');
                    $previewColor = $isCustomColor ? $customColor : '';
                @endphp

                <div class="card @if($isCustomColor) text-white @else bg-{{ $statColor }} text-white @endif" @if($isCustomColor) style="background-color: {{ $previewColor }} !important;" @endif>
                    <div class="card-body text-center py-2">
                        <i class="mdi {{ $statIcon }} fs-4"></i>
                        <div class="fw-bold">1,234</div>
                        <small>{{ $statLabel ?: 'نموذج إحصائية' }}</small>
                    </div>
                </div>
            </div>
        </div>

        @if($statType !== 'count' && empty($statField))
            <div class="alert alert-warning mt-3">
                <i class="mdi mdi-alert me-2"></i>
                <strong>تنبيه:</strong> يجب اختيار حقل رقمي للعمليات الحسابية (مجموع، متوسط، أقصى، أدنى).
            </div>
        @endif
    </div>
</div>
