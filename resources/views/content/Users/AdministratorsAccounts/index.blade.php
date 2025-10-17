@extends('layouts/layoutMaster')

@section('title', 'حسابات المشرفين')

@section('vendor-style')
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}">
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />

	<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}" />
	<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}" />

	<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
@endsection

@section('content')

	@livewire('users.administrators-accounts.administrators-accounts')

@endsection

@section('vendor-script')
	<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/cleavejs/cleave.js')}}"></script>
	<script src="{{asset('assets/vendor/libs/cleavejs/cleave-phone.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>

	<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
@endsection

@section('page-script')
	<script src="{{asset('assets/js/app-user-list.js')}}"></script>

	<script src="{{asset('assets/js/extended-ui-sweetalert2.js')}}"></script>
	<script src="{{asset('assets/js/form-basic-inputs.js')}}"></script>

	<script>

		$(document).ready(function() {
            window.initAdministratorRolesDrop=()=>{
                $('#SearchRole').select2({
					placeholder: 'حدد دور المشرف'
				})
            }
            initAdministratorRolesDrop();
            $('#SearchRole').on('change', function (e) {
                livewire.emit('SelectAdministratorRoles', e.target.value)
            });
            window.livewire.on('select2',()=>{
                initAdministratorRolesDrop();
            });
        });

		$(document).ready(function() {
            window.initAdministratorStatusDrop=()=>{
                $('#SearchStatus').select2({
					placeholder: 'حدد حالة الحساب'
				})
            }
            initAdministratorStatusDrop();
            $('#SearchStatus').on('change', function (e) {
                livewire.emit('SelectAdministratorStatus', e.target.value)
            });
            window.livewire.on('select2',()=>{
                initAdministratorStatusDrop();
            });
        });

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

		window.addEventListener('success', event => {
			$('#addAdministratorModal').modal('hide');
			$('#EditAdministrator').modal('hide');
			$('#RmoveAdministrator').modal('hide');
			Toast.fire({
				icon: 'success',
				title: event.detail.title + '<hr>' + event.detail.message,
			})
        });
	</script>
@endsection

