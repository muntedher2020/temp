@extends('layouts/layoutMaster')

@section('title', 'ادوار المستخدمين')

@section('vendor-style')
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />

	<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}" />
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}" />
@endsection

@section('content')

	@livewire('owner.permissions-roles.roles.account-roles')

@endsection

@section('vendor-script')
	<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>
@endsection

@section('page-script')
	<script src="{{asset('assets/js/app-access-roles.js')}}"></script>
	<script src="{{asset('assets/js/modal-add-role.js')}}"></script>

	<script src="{{asset('assets/js/extended-ui-sweetalert2.js')}}"></script>
	<script src="{{asset('assets/js/form-basic-inputs.js')}}"></script>

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
		window.addEventListener('RoleModalShow', event => 
            {
                $("#RoleNameModal").focus();
            }
        );

		window.addEventListener('RoleAddSuccess', 
            event => {
				Toast.fire({
					icon: 'success',
					title: 'تم اضافة الدور بنجاح'
				})

				$("#RoleNameModal").focus();
            }
        );
		window.addEventListener('RoleAddError', 
            event => {
				Toast.fire({
					icon: 'error',
					title: 'لم يتم تحديد صلاحيات للدور'
				})

				$("#RoleNameModal").focus();
            }
        );
		window.addEventListener('RoleUpdateSuccess', 
            event => {
                $('#editRoleModal').modal('hide');

				Toast.fire({
					icon: 'success',
					title: 'تم تعديل الدور بنجاح'
				})
            }
        );
		window.addEventListener('RoleDestroySuccess', 
            event => {
                $('#removeRoleModal').modal('hide');

				Toast.fire({
					icon: 'success',
					title: 'تم حذف الدور بنجاح'
				})
            }
        );
	</script>
@endsection