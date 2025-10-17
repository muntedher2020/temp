@extends('layouts/layoutMaster')
@section('title', 'الموظفين')
@section('vendor-style')
    <link rel="stylesheet"href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet"href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css" />
@endsection
@section('content')

    @livewire('employees.employee')

@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-user-list.js') }}"></script>
    <script src="{{ asset('assets/js/extended-ui-sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/js/form-basic-inputs.js') }}"></script>
    <script>
        // Initialize Flatpickr for date fields
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr for all date inputs
            flatpickr('.flatpickr-date', {
                dateFormat: 'Y-m-d',
                locale: 'ar'
            });

            flatpickr('.flatpickr-datetime', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i:S',
                locale: 'ar',
                time_24hr: true
            });

            // Month/Year picker - using monthSelectPlugin
            flatpickr('.flatpickr-month-year', {
                placeholder: 'التاريخ',
                altInput: true,
                allowInput: true,
                dateFormat: 'Y-m',
                altFormat: 'F Y',
                yearSelectorType: 'input',
                locale: {
                    months: {
                        shorthand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ],
                        longhand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ]
                    }
                },
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true,
                        dateFormat: 'Y-m',
                        altFormat: 'F Y',
                        theme: 'light'
                    })
                ]
            });

            // Initialize Select2 for search fields
            $('.select2-search').select2({
                placeholder: 'بحث...',
                allowClear: true,
                width: '100%'
            });

            // إضافة callbacks للحقول المحسوبة للتاريخ
            initializeDateCalculationCallbacks();
        });

        // دالة تفعيل callbacks للحقول المحسوبة للتاريخ
        function initializeDateCalculationCallbacks() {
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        window.addEventListener('EmployeeModalShow', event => {
            setTimeout(() => {
                $('#id').focus();
            }, 100);
        })

        window.addEventListener('success', event => {
            $('#addemployeeModal').modal('hide');
            $('#editemployeeModal').modal('hide');
            $('#removeemployeeModal').modal('hide');

            // تنظيف مؤشرات الملفات عند النجاح
            setTimeout(() => {
                clearFileIndicators('');
                clearFileIndicators('Edit');
            }, 500);

            Toast.fire({
                icon: 'success',
                title: event.detail.title + '<hr>' + event.detail.message,
            })
        })

        window.addEventListener('error', event => {
            $('#removeemployeeModal').modal('hide');
            Toast.fire({
                icon: 'error',
                title: event.detail.title + '<hr>' + event.detail.message,
                timer: 8000,
            })
        })

        // Print file function - طباعة مع معالجة خاصة للـ PDF
    function printFile(fileUrl) {
        if (!fileUrl) {
            alert('لا يوجد ملف للطباعة');
            return;
        }

        // تحديد نوع الملف
        const fileExtension = fileUrl.split('.').pop().toLowerCase();
        const isPDF = fileExtension === 'pdf';

        if (isPDF) {
            // للـ PDF فتح في نافذة جديدة مع إعطاء المستخدم التحكم الكامل
            const printWindow = window.open(
                fileUrl,
                '_blank',
                'width=1000,height=700,scrollbars=yes,resizable=yes,toolbar=yes,menubar=yes'
            );

            if (printWindow) {
                // إعطاء المستخدم وقت لرؤية الملف قبل عرض نافذة الطباعة
                printWindow.addEventListener('load', function() {
                    setTimeout(() => {
                        printWindow.focus();
                        // عرض نافذة الطباعة دون إغلاق النافذة تلقائياً
                        printWindow.print();
                        // السماح للمستخدم بإغلاق النافذة بنفسه
                    }, 1500);
                });

                // backup timeout في حالة عدم تحميل الـ load event
                setTimeout(() => {
                    if (printWindow && !printWindow.closed) {
                        try {
                            printWindow.focus();
                            printWindow.print();
                        } catch (e) {
                            console.log('PDF print backup failed:', e);
                        }
                    }
                }, 3000);
            } else {
                alert('فشل في فتح نافذة الطباعة. تحقق من إعدادات النوافذ المنبثقة.');
            }
        } else {
            // للصور والملفات الأخرى - iframe مخفي
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.left = '-9999px';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.src = fileUrl;

            document.body.appendChild(iframe);

            iframe.onload = function() {
                setTimeout(() => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        setTimeout(() => {
                            if (document.body.contains(iframe)) {
                                document.body.removeChild(iframe);
                            }
                        }, 1000);
                    } catch (e) {
                        console.log('Image print failed:', e);
                        const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                        if (printWindow) {
                            printWindow.onload = function() {
                                printWindow.print();
                                printWindow.close();
                            };
                        }
                        if (document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }
                }, 500);
            };

            iframe.onerror = function() {
                console.log('Image iframe load failed');
                const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                if (printWindow) {
                    printWindow.onload = function() {
                        printWindow.print();
                        printWindow.close();
                    };
                }
                if (document.body.contains(iframe)) {
                    document.body.removeChild(iframe);
                }
            };
        }
    }

        // دالة طباعة PDF - بساطة مثل زر العرض
        function printPDF(fileUrl) {
            // فتح PDF في نافذة جديدة مع خيارات طباعة محسنة
            const printWindow = window.open(
                fileUrl,
                '_blank',
                'width=1000,height=700,scrollbars=yes,resizable=yes,toolbar=yes,menubar=yes'
            );

            if (printWindow) {
                // تركيز على النافذة الجديدة ثم عرض خيارات الطباعة
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 2000);
            } else {
                alert('فشل في فتح نافذة الطباعة. تحقق من إعدادات النوافذ المنبثقة.');
            }
        }

        // دالة طباعة الصور
        function printImage(fileUrl) {
            // إنشاء iframe مخفي لتحميل وطباعة الصورة
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.left = '-9999px';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.src = fileUrl;

            document.body.appendChild(iframe);

            // انتظار تحميل المحتوى ثم الطباعة مباشرة
            iframe.onload = function() {
                setTimeout(() => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        // إزالة الـ iframe بعد الطباعة
                        setTimeout(() => {
                            if (document.body.contains(iframe)) {
                                document.body.removeChild(iframe);
                            }
                        }, 1000);
                    } catch (e) {
                        console.log('Image print failed:', e);
                        // في حالة فشل الـ iframe، استخدم النافذة المخفية
                        const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                        if (printWindow) {
                            printWindow.onload = function() {
                                printWindow.print();
                                printWindow.close();
                            };
                        }
                        if (document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }
                }, 500);
            };

            // في حالة فشل تحميل الـ iframe
            iframe.onerror = function() {
                console.log('Image iframe load failed');
                const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                if (printWindow) {
                    printWindow.onload = function() {
                        printWindow.print();
                        printWindow.close();
                    };
                }
                if (document.body.contains(iframe)) {
                    document.body.removeChild(iframe);
                }
            };
        }

        // Function to show file selection indicator with icon - محسنة للثبات
        function showFileSelected(input, indicatorId) {
            const indicator = document.getElementById(indicatorId);
            const fileName = document.getElementById(indicatorId.replace('fileSelected', 'fileName'));

            if (input.files.length > 0) {
                const file = input.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
                const fileInfo = {
                    name: file.name,
                    size: fileSize,
                    timestamp: Date.now(),
                    inputId: input.id
                };

                // حفظ معلومات الملف في localStorage فوراً
                localStorage.setItem('fileSelected_' + indicatorId, JSON.stringify(fileInfo));

                // إظهار المؤشر فوراً
                displayFileIndicator(indicatorId, fileInfo);

                // إضافة مراقب لإعادة الإظهار عند تحديث الصفحة
                setTimeout(() => {
                    restoreFileIndicators();
                }, 100);

                // إضافة مراقب إضافي في حالة تأخر Livewire
                setTimeout(() => {
                    if (document.getElementById(indicatorId)) {
                        displayFileIndicator(indicatorId, fileInfo);
                    }
                }, 500);

            } else {
                // إزالة معلومات الملف من localStorage عند عدم اختيار ملف
                localStorage.removeItem('fileSelected_' + indicatorId);
                if (indicator) {
                    indicator.style.display = 'none';
                }
            }
        }

        // دالة منفصلة لإظهار المؤشر
        function displayFileIndicator(indicatorId, fileInfo) {
            const indicator = document.getElementById(indicatorId);
            const fileName = document.getElementById(indicatorId.replace('fileSelected', 'fileName'));

            if (fileName && fileInfo) {
                fileName.textContent = fileInfo.name + ' (' + fileInfo.size + ' MB)';
            }

            if (indicator) {
                indicator.style.display = 'block';

                // Add animation effect only if not already visible
                if (indicator.style.opacity !== '1') {
                    indicator.style.opacity = '0';
                    setTimeout(() => {
                        indicator.style.transition = 'opacity 0.3s ease-in-out';
                        indicator.style.opacity = '1';
                    }, 50);
                }
            }
        }

        // دالة استعادة حالة الملفات المحفوظة - محسنة
        function restoreFileIndicators() {
            // البحث عن جميع مؤشرات الملفات
            const indicators = document.querySelectorAll('[id^="fileSelected"]');

            indicators.forEach(indicator => {
                const indicatorId = indicator.id;

                // استرجاع معلومات الملف من localStorage
                const savedFileInfo = localStorage.getItem('fileSelected_' + indicatorId);

                if (savedFileInfo) {
                    try {
                        const fileInfo = JSON.parse(savedFileInfo);

                        // التحقق من أن المعلومات ليست قديمة (أقل من 10 دقائق)
                        const tenMinutes = 10 * 60 * 1000;
                        if (Date.now() - fileInfo.timestamp < tenMinutes) {
                            displayFileIndicator(indicatorId, fileInfo);
                        } else {
                            // إزالة المعلومات القديمة
                            localStorage.removeItem('fileSelected_' + indicatorId);
                        }
                    } catch (e) {
                        // إزالة البيانات التالفة
                        localStorage.removeItem('fileSelected_' + indicatorId);
                    }
                }
            });
        }        // دالة تنظيف مؤشرات الملفات عند إغلاق المودال
        function clearFileIndicators(modalType) {
            const indicators = document.querySelectorAll('[id*="fileSelected' + modalType + '"]');
            indicators.forEach(indicator => {
                localStorage.removeItem('fileSelected_' + indicator.id);
                indicator.style.display = 'none';
            });
        }

        // Initialize flatpickr for search fields
        document.addEventListener('livewire:load', function () {
            // Initialize flatpickr for search date inputs
            const searchDateInputs = document.querySelectorAll('.flatpickr-input');
            searchDateInputs.forEach(function(input) {
                if (!input.classList.contains('flatpickr-initialized')) {
                    let config = {
                        dateFormat: 'Y-m-d',
                        locale: 'ar',
                        allowInput: true
                    };

                    // Different config for different date types
                    if (input.classList.contains('flatpickr-datetime')) {
                        config.enableTime = true;
                        config.dateFormat = 'Y-m-d H:i:S';
                        config.time_24hr = true;
                    } else if (input.classList.contains('flatpickr-month-year')) {
                        config.placeholder = 'التاريخ';
                        config.altInput = true;
                        config.allowInput = true;
                        config.dateFormat = 'Y-m';
                        config.altFormat = 'F Y';
                        config.yearSelectorType = 'input';
                        config.locale = {
                            months: {
                                shorthand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                                    'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                                ],
                                longhand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                                    'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                                ]
                            }
                        };
                        config.plugins = [
                            new monthSelectPlugin({
                                shorthand: true,
                                dateFormat: 'Y-m',
                                altFormat: 'F Y',
                                theme: 'light'
                            })
                        ];
                    }

                    const fp = flatpickr(input, config);
                    input.classList.add('flatpickr-initialized');

                    // Sync with Livewire for search fields
                    fp.config.onChange.push(function(selectedDates, dateStr, instance) {
                        // Update the input value and trigger Livewire update
                        input.value = dateStr;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }
            });

            // استعادة مؤشرات الملفات المرفوعة
            restoreFileIndicators();
        });

        // استعادة مؤشرات الملفات بعد تحديثات Livewire
        document.addEventListener('livewire:updated', function () {
            setTimeout(() => {
                restoreFileIndicators();
            }, 100);
        });

        // إضافة مراقب DOM للتأكد من ثبات الأيقونات
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldRestore = false;
                mutations.forEach(function(mutation) {
                    // التحقق من إضافة أو إزالة عقد تحتوي على file input
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        const hasFileInput = addedNodes.some(node => {
                            return node.nodeType === 1 &&
                                   (node.querySelector &&
                                    node.querySelector('[id*="fileSelected"]'));
                        });
                        if (hasFileInput) {
                            shouldRestore = true;
                        }
                    }
                });

                if (shouldRestore) {
                    setTimeout(() => {
                        restoreFileIndicators();
                    }, 200);
                }
            });

            // مراقبة تغييرات في body
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

    // Better Select2 integration with Livewire - Fixed version
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2 for all modals
        function initSelect2ForModal(modalId) {
            const selectFields = document.querySelectorAll(modalId + ' select');

            selectFields.forEach(function(select) {
                if (select.id && !$(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2({
                        placeholder: select.querySelector('option[value=""]')?.textContent || 'اختر',
                        allowClear: true,
                        width: '100%',
                        dir: 'rtl',
                        dropdownParent: $(modalId),
                        // Prevent Select2 from closing on select
                        closeOnSelect: true
                    });

                    // Enhanced Sync with Livewire v2 for wire:ignore elements
                    $(select).on('select2:select select2:unselect', function (e) {
                        const fieldName = this.getAttribute('wire:model.defer') || this.getAttribute('wire:model');
                        if (fieldName) {
                            // For Livewire v2 with wire:ignore - use component.set()
                            const livewireEl = this.closest('[wire\\:id]');
                            if (livewireEl && window.livewire) {
                                const componentId = livewireEl.getAttribute('wire:id');
                                const component = window.livewire.find(componentId);
                                if (component) {
                                    component.set(fieldName, this.value);
                                }
                            } else {
                                // Fallback method - trigger change event
                                $(this).trigger('change');
                            }
                        }
                    });
                }
            });
        }

        // Initialize for add modal
        $('#addemployeeModal').on('shown.bs.modal', function () {
            setTimeout(() => {
                initSelect2ForModal('#addemployeeModal');
            }, 100);
        });

        // Initialize for edit modal
        $('#editemployeeModal').on('shown.bs.modal', function () {
            setTimeout(() => {
                initSelect2ForModal('#editemployeeModal');
            }, 100);
        });

        // Reinitialize when Livewire updates - Livewire v2 syntax
        document.addEventListener('livewire:load', function() {
            window.livewire.hook('message.processed', (message, component) => {
                setTimeout(function() {
                    if ($('#addemployeeModal').hasClass('show')) {
                        // Destroy and reinitialize
                        $('#addemployeeModal select').each(function() {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                        });
                        initSelect2ForModal('#addemployeeModal');
                    }

                    if ($('#editemployeeModal').hasClass('show')) {
                        // Destroy and reinitialize
                        $('#editemployeeModal select').each(function() {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                        });
                        initSelect2ForModal('#editemployeeModal');
                    }
                }, 150);
            });
        });

        // Clean up Select2 when modals are hidden
        $('#addemployeeModal, #editemployeeModal').on('hidden.bs.modal', function () {
            $(this).find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
        });
    });
    </script>
@endsection