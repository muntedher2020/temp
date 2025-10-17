<!-- Remove Administrator Modal -->
<div wire:ignore.self class="modal fade" id="RmoveUserModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="p-3 modal-content p-md-5">
			<button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
			<div class="modal-body p-md-0">
				<div class="mb-4 text-center">
					<h3 class="pb-1 mb-2 text-danger">خذف حساب المستخدم</h3>
				</div>

				<form id="removeUserForm" class="pt-2 row" onsubmit="return false">
					<div class="row">
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input wire:model='name' type="text" readonly="" class="form-control-plaintext" id="UserName">
								<label for="UserName">أسم المستخدم</label>
							</div>
						</div>
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input  wire:model='email' type="text" readonly="" class="form-control-plaintext" id="UserEmail">
								<label for="UserEmail">البريد الالكتروني</label>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input  wire:model='UserRolesName' type="text" readonly="" class="form-control-plaintext" id="UserRolesName">
								<label for="UserRolesName">الدور</label>
							</div>
						</div>
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input  wire:model='store_name' type="text" readonly="" class="form-control-plaintext" id="UserRolesName">
								<label for="store_name">المحل</label>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input wire:model='status' type="text" readonly="" class="form-control-plaintext" id="UserStatus">
								<label for="UserStatus">حالة المستخدم</label>
							</div>
						</div>
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-danger">
								<input  wire:model='plan' type="text" readonly="" class="form-control-plaintext" id="UserPlan">
								<label for="UserPlan">خطة العمل</label>
							</div>
						</div>
					</div>

					<div class="text-center col-12">
						<hr>
						<button wire:click='destroy' type="submit" class="btn btn-danger me-sm-3 me-1">حذف</button>
						<button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
							aria-label="Close">تجاهل</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<!--/ Remove Administrator Modal -->