<!-- Edit Administrator Modal -->
<div wire:ignore.self class="modal fade" id="EditUserModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="p-3 modal-content p-md-5">
			<button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
			<div class="modal-body p-md-0">
				<div class="mb-4 text-center">
					<h3 class="pb-1 mb-2">تحرير حساب المستخدم</h3>
				</div>
				<form id="editUserForm" class="pt-2 row" onsubmit="return false">
					<div class="row">
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-primary">
								<input wire:model.defer='name' type="text" id="modalName" class="form-control" placeholder="اسم المشرف">
								<label for="modalName">اسم المستخدم</label>
							</div>
							@error('name')
								<small class='text-danger inputerror'> {{ $message }} </small>
							@enderror
						</div>
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-primary">
								<input wire:model.defer='email' type="text" id="modalEmail" class="form-control" placeholder="البريد الالكتروني">
								<label for="modalEmail">البريد الالكتروني</label>
							</div>
							@error('email')
								<small class='text-danger inputerror'> {{ $message }} </small>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<div class="col-6">
							<div class="form-floating form-floating-outline">
								<input wire:model.defer='password' type="text" id="Password" class="form-control" placeholder="كلمة المرور" />
								<label for="Password">كلمة المرور</label>
							</div>
							@error('password')
								<small class='text-danger inputerror'> {{ $message }} </small>
							@enderror
                        </div>
						<div class="col-6">
							<div class="form-floating form-floating-outline">
								<input wire:model.defer='ConfirmPassword' type="text" id="Confirm-Password" class="form-control" placeholder="تأكيد كلمة المرور" />
								<label for="Confirm-Password">تأكيد كلمة المرور</label>
							</div>
                        </div>
					</div>
					<div class="row">
						<div class="mb-3 col-6">
							<div class="form-floating form-floating-outline text-primary">
								<select wire:model.defer='UserRoles' id="UserRoles" class="form-select h-100" multiple data-placeholder="Select a Roles">
									@foreach ($Roles as $Role)
										@if ( $Role->name != 'OWNER' AND $Role->name != 'Supervisor' )
											<option value="{{ $Role->id }}">{{ $Role->name }}</option>
										@endif
									@endforeach
									{{-- @foreach ($Roles as $Role)
										@if ( Auth::User()->hasRole('OWNER') OR ($Role->name != 'OWNER') )
											<option value="{{ $Role->id }}">{{ $Role->name }}</option>
										@endif
									@endforeach --}}
								</select>
								<label for="UserRoles">الدور</label>
							</div>
							@error('UserRoles')
								<small class='text-danger inputerror'> {{ $message }} </small>
							@enderror
						</div>
                        <div class="col-6">
                            <div class="form-floating form-floating-outline">
                                <select wire:model.defer="plan" class="form-control" id="AdministratorPlan">
                                    <option value="مستخدم">مستخدم</option>
                                </select>
                                <label id="AdministratorPlan">خطة العمل</label>
                                @error('plan')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-2 form-floating form-floating-outline mt-3">
                                <select wire:model.defer="status" class="form-control" id="AdministratorStatus">
                                    <option value=""></option>
                                    <option value="1">مفعل</option>
                                    <option value="0">غير مفعل</option>
                                </select>
                                <label id="AdministratorStatus">حالة المستخدم</label>
                                @error('status')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
						</div>
					</div>

					<div class="text-center col-12">
						<hr>
						<button wire:click='update' type="submit" class="btn btn-primary me-sm-3 me-1">تعديل</button>
						<button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
							aria-label="Close">تجاهل</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<!--/ Edit Administrator Modal -->
