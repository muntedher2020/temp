@extends('layouts/layoutMaster')
@section('title', 'إدارة الوحدات')

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
    @livewire('module-manager.module-manager-simple')
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
            timer: 2000,
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

        window.addEventListener('info', event => {
            Toast.fire({
                icon: 'info',
                title: event.detail.title + '<hr>' + event.detail.message,
            })
        })

        window.addEventListener('warning', event => {
            Toast.fire({
                icon: 'warning',
                title: event.detail.title + '<hr>' + event.detail.message,
                timer: 6000,
            })
        })

        // دالة لإضافة خيار جديد للقائمة المنسدلة
        function addNewSelectOption() {
            const input = document.getElementById('newSelectOption');
            const value = input?.value?.trim();

            if (value && window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    component.call('addSelectOption', value);
                    input.value = '';
                    input.focus();
                }
            }
        }

        // دالة لتغيير نوع الحقل
        function changeFieldType() {
            if (window.Livewire) {
                const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    component.call('changeFieldType');
                }
            }
        }

        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // تهيئة Select2
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#fieldType').select2({
                    width: '100%',
                    placeholder: 'اختر نوع الحقل'
                }).on('change', function() {
                    changeFieldType();
                });
            }

            // استمع للأحداث
            document.addEventListener('livewire:load', function () {
                Livewire.on('selectOptionAdded', () => {
                    const input = document.getElementById('newSelectOption');
                    if (input) {
                        input.focus();
                    }
                });

                Livewire.on('clearSelectOptions', () => {
                    const input = document.getElementById('newSelectOption');
                    if (input) {
                        input.value = '';
                    }
                });
            });
        });

        // إضافة تأثيرات انتقالية للـ nav pills
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-pills .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // إضافة تأثير تحميل مؤقت
                    if (!this.classList.contains('active')) {
                        const originalText = this.innerHTML;
                        this.innerHTML += ' <i class="bx bx-loader-alt bx-spin ms-2"></i>';

                        setTimeout(() => {
                            this.innerHTML = originalText;
                        }, 1000);
                    }
                });
            });
        });
    </script>
@endsection
