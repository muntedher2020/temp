@extends('layouts/layoutMaster')
@section('title', 'إدارة البيانات')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom-style.css') }}?v={{ time() }}" />
    <style>
        /* Inline CSS for immediate hover effects */
        .card.cursor-pointer {
            transition: all 0.2s ease-in-out !important;
        }
        .card.cursor-pointer:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            border-color: #696cff !important;
        }
        .card.cursor-pointer:active {
            transform: translateY(0) !important;
        }
    </style>
@endsection

@section('content')
    @livewire('data-management.data-management-main')
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
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/locales/ar.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-user-list.js') }}"></script>
    <script src="{{ asset('assets/js/extended-ui-sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/js/form-basic-inputs.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initFileUpload();
        });

        // إعادة تشغيل عند تحديث Livewire
        document.addEventListener('livewire:load', function () {
            initFileUpload();
        });

        // إعادة تشغيل بعد كل طلب Livewire
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(() => {
                    initFileUpload();
                }, 100);
            });
        }

        function initFileUpload() {
            // إعداد Drag & Drop للملفات
            const fileDropArea = document.getElementById('fileDropArea');
            const fileInput = document.getElementById('importFile');

            if (fileDropArea && fileInput) {
                // إزالة المستمعين القدامى أولاً لتجنب التكرار
                const events = ['dragenter', 'dragover', 'dragleave', 'drop'];
                events.forEach(eventName => {
                    fileDropArea.removeEventListener(eventName, preventDefaults);
                    document.body.removeEventListener(eventName, preventDefaults);
                });

                // منع السلوك الافتراضي للمتصفح
                events.forEach(eventName => {
                    fileDropArea.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                // إضافة تأثيرات بصرية عند السحب
                ['dragenter', 'dragover'].forEach(eventName => {
                    fileDropArea.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    fileDropArea.addEventListener(eventName, unhighlight, false);
                });

                // معالجة إسقاط الملف
                fileDropArea.addEventListener('drop', handleDrop, false);

                // معالجة تغيير الملف عبر النقر
                fileInput.addEventListener('change', handleFileSelect, false);

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                function highlight(e) {
                    fileDropArea.classList.add('dragover');
                }

                function unhighlight(e) {
                    fileDropArea.classList.remove('dragover');
                }

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;

                    if (files.length > 0) {
                        handleFile(files[0]);
                    }
                }

                function handleFileSelect(e) {
                    if (e.target.files.length > 0) {
                        handleFile(e.target.files[0]);
                    }
                }

                function handleFile(file) {
                    // التحقق من نوع الملف
                    const allowedTypes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        'application/vnd.ms-excel', // .xls
                        'text/csv' // .csv
                    ];

                    const allowedExtensions = ['.xlsx', '.xls', '.csv'];
                    const fileName = file.name.toLowerCase();
                    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

                    if (allowedTypes.includes(file.type) || hasValidExtension) {
                        // التحقق من حجم الملف (10MB)
                        if (file.size <= 10 * 1024 * 1024) {

                            // تحديث input file إذا تم السحب والإفلات
                            if (!fileInput.files.length || fileInput.files[0] !== file) {
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                fileInput.files = dataTransfer.files;
                            }

                            // تحديث النص في الواجهة فوراً
                            updateFileDisplay(file);

                            // إشعار Livewire بالتغيير
                            notifyLivewireFileChange(file);

                        } else {
                            alert('حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت.');
                            clearFileInput();
                        }
                    } else {
                        alert('نوع الملف غير مدعوم. يرجى اختيار ملف Excel أو CSV.');
                        clearFileInput();
                    }
                }

                function updateFileDisplay(file) {
                    const dropText = fileDropArea.querySelector('#dropAreaText');
                    const dropSubtext = fileDropArea.querySelector('#dropAreaSubtext');

                    if (dropText && dropSubtext) {
                        dropText.textContent = `تم اختيار: ${file.name}`;
                        dropSubtext.textContent = `${(file.size / 1024).toFixed(2)} KB`;
                        fileDropArea.classList.add('file-selected');
                    }
                }

                function clearFileInput() {
                    fileInput.value = '';
                    const dropText = fileDropArea.querySelector('#dropAreaText');
                    const dropSubtext = fileDropArea.querySelector('#dropAreaSubtext');

                    if (dropText && dropSubtext) {
                        dropText.textContent = 'انقر لاختيار الملف أو اسحبه هنا';
                        dropSubtext.textContent = 'يدعم ملفات Excel (XLSX, XLS) و CSV - حد أقصى 10 ميجابايت';
                        fileDropArea.classList.remove('file-selected');
                    }
                }

                function notifyLivewireFileChange(file) {
                    if (typeof Livewire !== 'undefined') {
                        // محاولة العثور على component إدارة البيانات
                        try {
                            const wireElement = document.querySelector('[wire\\:id]');
                            if (wireElement) {
                                const componentId = wireElement.getAttribute('wire:id');
                                const component = Livewire.find(componentId);
                                if (component) {
                                    // تحديث الملف في Livewire component
                                    component.set('importFile', file);
                                    console.log('تم إشعار Livewire بنجاح:', file.name);
                                }
                            }
                        } catch (error) {
                            console.warn('خطأ في إشعار Livewire:', error);
                            // Fallback: استخدام طريقة بديلة
                            if (window.livewire) {
                                window.livewire.emit('fileUploaded', file.name);
                            }
                        }
                    }
                }
            }

            // إعداد تحديث فوري للملف المختار
            if (fileInput) {
                // إزالة المستمع القديم أولاً
                fileInput.removeEventListener('change', handleFileInputChange);
                // إضافة المستمع الجديد
                fileInput.addEventListener('change', handleFileInputChange, false);
            }

            function handleFileInputChange(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    updateFileDisplayFromInput(file);
                }
            }

            function updateFileDisplayFromInput(file) {
                const area = document.getElementById('fileDropArea');
                if (area) {
                    const h6Element = area.querySelector('h6, #dropAreaText');
                    const smallElement = area.querySelector('small, #dropAreaSubtext');

                    if (h6Element) {
                        h6Element.textContent = `تم اختيار: ${file.name}`;
                    }
                    if (smallElement) {
                        smallElement.textContent = `${(file.size / 1024).toFixed(2)} KB`;
                    }
                    area.classList.add('file-selected');
                }
            }
        }

        // إعداد Livewire events للتفاعل مع التحديثات
        document.addEventListener('livewire:load', function () {
            // إعادة تعيين منطقة رفع الملفات بعد تحديث Livewire
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', (message, component) => {
                    const fileDropArea = document.getElementById('fileDropArea');
                    const fileInput = document.getElementById('importFile');

                    if (fileDropArea && fileInput && !fileInput.files.length) {
                        const h6Element = fileDropArea.querySelector('h6, #dropAreaText');
                        const smallElement = fileDropArea.querySelector('small, #dropAreaSubtext');

                        if (h6Element && h6Element.textContent.includes('تم اختيار:')) {
                            h6Element.textContent = 'انقر لاختيار الملف أو اسحبه هنا';
                        }
                        if (smallElement) {
                            smallElement.textContent = 'يدعم ملفات Excel (XLSX, XLS) و CSV - حد أقصى 10 ميجابايت';
                        }
                        fileDropArea.classList.remove('file-selected');
                    }
                });
            }
        });
    </script>
@endsection
