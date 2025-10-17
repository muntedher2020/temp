@extends('layouts/layoutMaster')

@section('title', 'تصاريح حسابات المستخدمين')

@section('vendor-style')
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />

	<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}" />
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}" />
@endsection

@section('content')

	@livewire('owner.permissions-roles.permissions.account-permissions')

@endsection


@section('vendor-script')
	<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>
@endsection

@section('page-script')
	<script src="{{asset('assets/js/app-access-permission.js')}}"></script>
	<script src="{{asset('assets/js/modal-add-permission.js')}}"></script>
	<script src="{{asset('assets/js/modal-edit-permission.js')}}"></script>

	<script src="{{asset('assets/js/extended-ui-sweetalert2.js')}}"></script>

	<script>
		const Toast = Swal.mixin({
			toast: true,
			position: 'top-start',
			showConfirmButton: false,
			timer: 3000,
			timerProgressBar: true,
			didOpen: (toast) => {
				toast.addEventListener('mouseenter', Swal.stopTimer)
				toast.addEventListener('mouseleave', Swal.resumeTimer)
			}
		})

		window.addEventListener('PermissionModalShow', event => 
            {
                $("#modalPermissionName").focus();
            }
        );
		window.addEventListener('PermissionAddSuccess', 
            event => {
				Toast.fire({
					icon: 'success',
					title: 'تم اضافة التصريح بنجاح'
				})

				$("#modalPermissionName").focus();
            }
        );
		window.addEventListener('PermissionUpdateSuccess', 
            event => {
                $('#editPermissionModal').modal('hide');

				Toast.fire({
					icon: 'success',
					title: 'تم تعديل التصريح بنجاح'
				})

				$("#modalPermissionName").focus();
            }
        );
		window.addEventListener('PermissionDestroySuccess', 
            event => {
                $('#removePermissionModal').modal('hide');

				Toast.fire({
					icon: 'success',
					title: 'تم حذف التصريح بنجاح'
				})
            }
        );
		window.addEventListener('PermissionNotFond', 
            event => {
                Toast.fire({
					icon: 'error',
					title: 'لم يتم العثور على تصريح'
				})
            }
        );
	</script>
@endsection