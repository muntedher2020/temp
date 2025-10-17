@extends('layouts/contentNavbarLayout')

@section('title', 'مصمم الداشبورد')

@section('content')

@livewire('dashboard-builder.dashboard-builder-main')
@endsection

@push('styles')
<style>
    .cursor-pointer {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .cursor-pointer:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .bg-light-primary {
        background-color: rgba(105, 108, 255, 0.1) !important;
    }
    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1) !important;
    }
    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }
    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    .nav-tabs .nav-link.active {
        color: var(--bs-primary);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color) var(--bs-border-color) var(--bs-body-bg);
        font-weight: 600;
    }
    .border-2 {
        border-width: 2px !important;
    }
    .card.cursor-pointer {
        position: relative;
        overflow: hidden;
    }
    .card.cursor-pointer::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s;
    }
    .card.cursor-pointer:hover::before {
        left: 100%;
    }

    /* أنماط نافذة الأيقونات */
    .icon-item {
        width: 60px;
        height: 60px;
        border: 2px solid transparent;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8f9fa;
        margin: 4px;
    }
    .icon-item:hover {
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
        transform: scale(1.1);
    }
    .icon-item.selected {
        background: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }
    .icon-item i {
        font-size: 24px;
    }
    #iconGrid {
        max-height: 400px;
        overflow-y: auto;
    }

    /* أنماط للعناصر المعطلة */
    .widget-disabled {
        opacity: 0.6;
        position: relative;
    }

    .widget-disabled::after {
        content: '⏸️ معطل';
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
    }

    .opacity-75 {
        opacity: 0.75 !important;
    }

    .opacity-50 {
        opacity: 0.5 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-start',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.style.top = '80px';
            toast.style.zIndex = '9999';
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })

    window.addEventListener('success', event => {
        Toast.fire({
            icon: 'success',
            title: event.detail.title + '<hr>' + event.detail.message,
        })
    })

    window.addEventListener('error', event => {
        Toast.fire({
            icon: 'error',
            title: event.detail.title + '<hr>' + event.detail.message,
            timer: 5000,
        })
    })

    window.addEventListener('warning', event => {
        Toast.fire({
            icon: 'warning',
            title: event.detail.title + '<hr>' + event.detail.message,
            timer: 4000,
        })
    })

    window.addEventListener('show-message', event => {
        let iconType = event.detail.type || 'info';
        let timer = 3000;

        if (iconType === 'error') timer = 5000;
        if (iconType === 'warning') timer = 4000;

        Toast.fire({
            icon: iconType,
            title: (event.detail.title || 'إشعار') + '<hr>' + event.detail.message,
            timer: timer,
        })
    })

    // إضافة تأثيرات تفاعلية للبطاقات
    document.addEventListener('click', function(e) {
        // إضافة تأثير ripple للبطاقات القابلة للنقر
        if (e.target.closest('.cursor-pointer')) {
            const card = e.target.closest('.cursor-pointer');
            const ripple = document.createElement('span');
            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.6);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            card.style.position = 'relative';
            card.style.overflow = 'hidden';
            card.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
    });

    // إضافة CSS للـ ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // إدارة نافذة اختيار الأيقونات
    let currentIconInput = null;
    let selectedIcon = null;

    // مجموعة واسعة من أيقونات MDI مصنفة حسب الفئات
    const mdiIcons = {
        account: [
            'account', 'account-circle', 'account-group', 'account-multiple', 'account-plus',
            'account-check', 'account-star', 'account-key', 'account-edit', 'account-settings',
            'face-man', 'face-woman', 'account-badge', 'account-card-details', 'account-heart',
            'account-supervisor', 'account-tie', 'account-voice', 'account-wrench', 'badge-account'
        ],
        chart: [
            'chart-line', 'chart-bar', 'chart-pie', 'chart-donut', 'chart-area',
            'chart-bubble', 'chart-column', 'chart-gantt', 'chart-histogram', 'chart-multiline',
            'chart-scatter-plot', 'chart-timeline', 'trending-up', 'trending-down', 'analytics',
            'poll', 'finance', 'graph', 'chart-arc', 'chart-bell-curve'
        ],
        file: [
            'file', 'file-document', 'file-pdf-box', 'file-excel', 'file-word',
            'file-powerpoint', 'file-image', 'file-video', 'file-music', 'file-code',
            'folder', 'folder-open', 'archive', 'zip-box', 'file-upload',
            'file-download', 'file-edit', 'file-check', 'file-plus', 'file-remove'
        ],
        calendar: [
            'calendar', 'calendar-today', 'calendar-month', 'calendar-week', 'calendar-range',
            'calendar-check', 'calendar-plus', 'calendar-edit', 'calendar-remove', 'clock',
            'clock-outline', 'timer', 'stopwatch', 'history', 'schedule',
            'alarm', 'calendar-star', 'calendar-heart', 'calendar-alert', 'calendar-blank'
        ],
        shopping: [
            'cart', 'shopping', 'store', 'cash', 'credit-card',
            'wallet', 'receipt', 'sale', 'tag', 'basket',
            'gift', 'currency-usd', 'currency-eur', 'point-of-sale', 'barcode',
            'package', 'truck-delivery', 'storefront', 'shopping-bag', 'coin'
        ],
        school: [
            'school', 'book', 'book-open', 'library', 'graduation-cap',
            'pencil', 'pen', 'notebook', 'calculator', 'microscope',
            'flask', 'test-tube', 'atom', 'dna', 'brain',
            'certificate', 'trophy', 'medal', 'award', 'teach'
        ],
        database: [
            'database', 'server', 'cloud', 'backup-restore', 'download',
            'upload', 'sync', 'refresh', 'cached', 'harddisk',
            'memory', 'chip', 'usb', 'lan', 'wifi',
            'router', 'console', 'api', 'web', 'database-plus'
        ],
        home: [
            'home', 'office-building', 'domain', 'factory', 'warehouse',
            'store-24-hour', 'hospital', 'bank', 'school-outline', 'church',
            'bridge', 'castle', 'city', 'map', 'map-marker',
            'home-variant', 'home-heart', 'home-group', 'home-city', 'home-modern'
        ]
    };

    // أيقونات شائعة
    const popularIcons = [
        'home', 'account', 'chart-line', 'file-document', 'calendar',
        'shopping', 'school', 'database', 'heart', 'star',
        'settings', 'bell', 'mail', 'phone', 'car',
        'airplane', 'camera', 'music', 'video', 'image'
    ];

    window.openIconPicker = function(inputElement) {
        currentIconInput = inputElement;
        selectedIcon = null;

        // إعادة تعيين حالة النافذة
        document.getElementById('iconSearch').value = '';
        document.getElementById('iconCategory').value = '';
        document.getElementById('selectedIconInfo').style.display = 'none';
        document.getElementById('selectIconBtn').disabled = true;

        // عرض الأيقونات الشائعة أولاً
        displayIcons(popularIcons);

        // فتح النافذة
        new bootstrap.Modal(document.getElementById('iconPickerModal')).show();
    };

    function displayIcons(iconsArray) {
        const iconGrid = document.getElementById('iconGrid');
        iconGrid.innerHTML = '';

        iconsArray.forEach(iconName => {
            const iconDiv = document.createElement('div');
            iconDiv.className = 'col-auto';
            iconDiv.innerHTML = `
                <div class="icon-item" onclick="selectIconItem('${iconName}', this)" title="${iconName}">
                    <i class="mdi mdi-${iconName}"></i>
                </div>
            `;
            iconGrid.appendChild(iconDiv);
        });
    }

    window.selectIconItem = function(iconName, element) {
        // إزالة التحديد من الأيقونات الأخرى
        document.querySelectorAll('.icon-item').forEach(item => {
            item.classList.remove('selected');
        });

        // تحديد الأيقونة الحالية
        element.classList.add('selected');
        selectedIcon = iconName;

        // عرض معلومات الأيقونة المحددة
        document.getElementById('selectedIconName').textContent = iconName;
        document.getElementById('selectedIconPreview').innerHTML = `<i class="mdi mdi-${iconName}"></i>`;
        document.getElementById('selectedIconInfo').style.display = 'block';
        document.getElementById('selectIconBtn').disabled = false;
    };

    // دالة للاختيار السريع للأيقونات الشائعة
    window.quickSelectIcon = function(iconName) {
        selectedIcon = iconName;

        // عرض معلومات الأيقونة
        document.getElementById('selectedIconName').textContent = iconName;
        document.getElementById('selectedIconPreview').innerHTML = `<i class="mdi mdi-${iconName}"></i>`;
        document.getElementById('selectedIconInfo').style.display = 'block';
        document.getElementById('selectIconBtn').disabled = false;

        // تحديد الأيقونة في الشبكة إذا كانت مرئية
        const iconElements = document.querySelectorAll('.icon-item');
        iconElements.forEach(item => {
            item.classList.remove('selected');
            if (item.title === iconName) {
                item.classList.add('selected');
            }
        });

        // اختيار الأيقونة مباشرة
        selectIcon();
    };

    window.selectIcon = function() {
        if (selectedIcon && currentIconInput) {
            // تحديد الصيغة المطلوبة (mdi-iconname أو iconname فقط)
            let iconValue = selectedIcon;

            // إذا كان الحقل يتوقع صيغة mdi- (مثل statIcon)
            if (currentIconInput.id === 'statIconInput' || currentIconInput.placeholder.includes('mdi-')) {
                iconValue = selectedIcon.startsWith('mdi-') ? selectedIcon : 'mdi-' + selectedIcon;
            }

            // تحديث قيمة الحقل
            currentIconInput.value = iconValue;

            // تحديث المعاينة إذا كانت موجودة
            const preview = currentIconInput.parentElement.querySelector('.icon-preview i');
            if (preview) {
                preview.className = `mdi mdi-${selectedIcon} text-primary`;
            }

            // تحديث معاينة الأيقونة في input-group-text
            const inputGroupIcon = currentIconInput.parentElement.querySelector('.input-group-text i');
            if (inputGroupIcon) {
                inputGroupIcon.className = `mdi ${iconValue} text-primary`;
            }

            // إطلاق أحداث متعددة لضمان التحديث
            currentIconInput.dispatchEvent(new Event('input', { bubbles: true }));
            currentIconInput.dispatchEvent(new Event('change', { bubbles: true }));

            // تحديث Livewire مباشرة إذا أمكن
            if (window.Livewire && currentIconInput.hasAttribute('wire:model')) {
                const component = currentIconInput.closest('[wire\\:id]');
                if (component) {
                    const componentId = component.getAttribute('wire:id');
                    const wireModel = currentIconInput.getAttribute('wire:model');

                    // استخدام Livewire لتحديث البيانات
                    try {
                        window.Livewire.find(componentId).set(wireModel, iconValue);
                    } catch (e) {
                        // تحديث عبر الأحداث التقليدية
                    }
                }
            }

            // إغلاق النافذة
            bootstrap.Modal.getInstance(document.getElementById('iconPickerModal')).hide();
        }
    };

    // البحث في الأيقونات
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('iconSearch')) {
            document.getElementById('iconSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const category = document.getElementById('iconCategory').value;
                filterIcons(searchTerm, category);
            });

            // تصفية حسب الفئة
            document.getElementById('iconCategory').addEventListener('change', function(e) {
                const category = e.target.value;
                const searchTerm = document.getElementById('iconSearch').value.toLowerCase();
                filterIcons(searchTerm, category);
            });

            // إعادة تعيين النافذة عند الإغلاق
            document.getElementById('iconPickerModal').addEventListener('hidden.bs.modal', function() {
                currentIconInput = null;
                selectedIcon = null;
            });
        }
    });

    function filterIcons(searchTerm, category) {
        let iconsToShow = [];

        if (category) {
            // عرض أيقونات الفئة المحددة
            iconsToShow = mdiIcons[category] || [];
        } else {
            // عرض جميع الأيقونات
            iconsToShow = Object.values(mdiIcons).flat();
        }

        // تطبيق البحث النصي
        if (searchTerm) {
            iconsToShow = iconsToShow.filter(icon =>
                icon.toLowerCase().includes(searchTerm)
            );
        }

        // إذا لم يكن هناك بحث أو فئة، عرض الأيقونات الشائعة
        if (!searchTerm && !category) {
            iconsToShow = popularIcons;
        }

        displayIcons(iconsToShow);
    }
</script>

<!-- تحميل Chart.js للمعاينة -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
