@extends('layouts/layoutMaster')
@section('title', 'مولد الوحدات')

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
@endsection

@section('content')
    @livewire('module-generator.module-generator')
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
                timer: 8000,
            })
        })

        // دالة التعامل مع تغيير نوع الحقل
        function handleFieldTypeChange() {
            const select = document.getElementById('fieldType');
            const selectedType = select.value;

            // تحديث قيمة Livewire
            if (window.livewire && window.livewire.components && window.livewire.components.getByName) {
                const component = window.livewire.components.getByName('module-generator.module-generator')[0];
                if (component) {
                    component.set('newField.type', selectedType);
                    // استدعاء دالة التغيير بعد تأخير قصير
                    setTimeout(() => {
                        component.call('changeFieldType');
                    }, 100);
                }
            } else if (window.Livewire) {
                // For Livewire v3
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    component.set('newField.type', selectedType);
                    setTimeout(() => {
                        component.call('changeFieldType');
                    }, 100);
                }
            }
        }

        // دالة إضافة خيار جديد للقائمة المنسدلة
        function addNewSelectOption() {
            console.log('بدء وظيفة إضافة خيار جديد');
            const input = document.getElementById('newSelectOption');
            console.log('تم العثور على الحقل:', input);

            if (!input) {
                console.error('لم يتم العثور على حقل newSelectOption');
                return;
            }

            const optionValue = input.value.trim();
            console.log('قيمة الخيار:', optionValue);

            if (optionValue) {
                console.log('استدعاء Livewire method مع القيمة:', optionValue);

                if (window.livewire && window.livewire.components && window.livewire.components.getByName) {
                    const component = window.livewire.components.getByName('module-generator.module-generator')[0];
                    if (component) {
                        component.call('addSelectOption', optionValue).then(() => {
                            console.log('تم إرسال الطلب بنجاح');
                            input.value = '';
                        }).catch(error => {
                            console.error('خطأ في إرسال الطلب:', error);
                        });
                    }
                } else if (window.Livewire) {
                    const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (component) {
                        component.call('addSelectOption', optionValue).then(() => {
                            console.log('تم إرسال الطلب بنجاح');
                            input.value = '';
                        }).catch(error => {
                            console.error('خطأ في إرسال الطلب:', error);
                        });
                    }
                }
            } else {
                console.warn('القيمة فارغة');
            }
        }

        // Handle Livewire events for clearing select options
        window.addEventListener('clearSelectOptions', event => {
            // تنظيف الخيارات عند تغيير نوع الحقل
            const input = document.getElementById('newSelectOption');
            if (input) {
                input.value = '';
            }
            console.log('تم تنظيف خيارات القائمة المنسدلة');
        });

        // تأكد من تحديث الواجهة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // تأكد من أن الحقول تعمل بشكل صحيح من البداية
            console.log('تم تحميل مولد الوحدات بنجاح');

            // تفعيل الحالة الأولية
            setTimeout(() => {
                const fieldType = document.getElementById('fieldType');
                if (fieldType) {
                    handleFieldTypeChange();
                }
            }, 500);

            // مراقبة تغيير نوع الوحدة
            document.addEventListener('livewire:load', function () {
                if (window.Livewire && window.Livewire.hook) {
                    Livewire.hook('message.processed', (message, component) => {
                        // تحديث المجموعات عند تغيير النوع
                        if (message.updateQueue.some(update => update.payload.name === 'moduleType')) {
                            setTimeout(() => {
                                const comp = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                                if (comp) {
                                    comp.call('updateAvailableGroups');
                                }
                            }, 100);
                        }
                    });
                }
            });
        });

        // وظيفة لتحديث حقول الجدول عند تغيير الجدول المختار
        function updateTableColumns(tableName) {
            if (window.livewire && window.livewire.components && window.livewire.components.getByName) {
                const component = window.livewire.components.getByName('module-generator.module-generator')[0];
                if (component) {
                    if (tableName) {
                        component.call('loadTableColumns', tableName).then(() => {
                            // إعادة تعيين قيم المفتاح والعرض
                            component.set('newField.related_key', 'id');
                            component.set('newField.related_display', '');
                        });
                    } else {
                        component.set('selectedTableColumns', []);
                        component.set('newField.related_key', 'id');
                        component.set('newField.related_display', '');
                    }
                }
            } else if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    if (tableName) {
                        component.call('loadTableColumns', tableName).then(() => {
                            component.set('newField.related_key', 'id');
                            component.set('newField.related_display', '');
                        });
                    } else {
                        component.set('selectedTableColumns', []);
                        component.set('newField.related_key', 'id');
                        component.set('newField.related_display', '');
                    }
                }
            }
        }

        // مراقبة التغييرات في نوع الوحدة
        window.addEventListener('livewire:load', function () {
            // تحديث المجموعات المتاحة عند تغيير نوع الوحدة
            if (window.livewire && window.livewire.components && window.livewire.components.getByName) {
                const component = window.livewire.components.getByName('module-generator.module-generator')[0];
                if (component) {
                    component.on('moduleTypeChanged', () => {
                        component.call('updateAvailableGroups');
                    });
                }
            } else if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    component.on('moduleTypeChanged', () => {
                        component.call('updateAvailableGroups');
                    });
                }
            }
        });
    </script>
@endsection
