@extends('layouts/layoutMaster')
@section('title', 'إدارة المجموعات الأساسية')

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
    @livewire('management.basic-group-management')
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
    <script src="{{ asset('assets/js/form-basic-inputs.js') }}"></script>
    <script>
        // تكوين Toast للإشعارات العادية (بدون أزرار)
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-start',        // تغيير الموقع إلى اليسار
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // تكوين Modal للرسائل المهمة (مع زر حسناً)
        const ModalAlert = Swal.mixin({
            confirmButtonText: 'حسناً',
            showConfirmButton: true,
            showCancelButton: false,
            showDenyButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
                const livewireComponent = document.querySelector('[wire\\:id]');
                if (livewireComponent) {
                    Livewire.find(livewireComponent.getAttribute('wire:id')).call('closeModal');
                }
            }
        });

        // Success messages - Toast على اليسار
        window.addEventListener('success', event => {
            Toast.fire({
                icon: 'success',
                title: event.detail.title,
                text: event.detail.message
            });
        });

        // Error messages - Modal مع زر حسناً
        window.addEventListener('error', event => {
            ModalAlert.fire({
                icon: 'error',
                title: event.detail.title,
                text: event.detail.message,
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        });

        // Warning messages - Toast على اليسار
        window.addEventListener('warning', event => {
            Toast.fire({
                icon: 'warning',
                title: event.detail.title,
                text: event.detail.message,
                timer: 4000
            });
        });

        // Info messages - Toast على اليسار
        window.addEventListener('info', event => {
            Toast.fire({
                icon: 'info',
                title: event.detail.title,
                text: event.detail.message
            });
        });
    </script>
@endsection
