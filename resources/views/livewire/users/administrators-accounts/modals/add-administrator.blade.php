<!-- Add Administrator Modal -->
<div wire:ignore.self class="modal fade" id="addAdministratorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="mb-4 text-center mt-n4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold mb-2">
                        <span class="text-primary">اضافة</span> مشرف جديد
                    </h3>
                    <p class="text-muted">
                        <i class="mdi mdi-account-tie me-1"></i>
                        قم بإدخال بيانات المشرف في النموذج أدناه
                    </p>
                </div>

                <hr class="text-primary mt-n2">

                <form id="addAdministratorForm" class="pt-2 row" onsubmit="return false" autocomplete="off">
                    <div class="row">
                        <div class="mb-3 col-6">
                            <div class="form-floating form-floating-outline">
                                <input wire:model.defer='name' type="text" class="form-control" id="modalName"
                                    placeholder="أسم المشرف">
                                <label for="modalName">اسم المشرف</label>
                            </div>
                            @error('name')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                            @enderror
                        </div>
                        <div class="mb-3 col-6">
                            <div class="form-floating form-floating-outline">
                                <input wire:model.defer='email' type="text" class="form-control" id="modalEmail"
                                    placeholder="البريد الالكتروني">
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
                                <input wire:model.defer='password' type="text" id="Password" class="form-control"
                                    placeholder="كلمة المرور" />
                                <label for="Password">كلمة المرور</label>
                            </div>
                            @error('password')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                            @enderror
                        </div>
                        <div class="col-6">
                            <div class="form-floating form-floating-outline">
                                <input wire:model.defer='ConfirmPassword' type="text" id="Confirm-Password"
                                    class="form-control" placeholder="تأكيد كلمة المرور" />
                                <label for="Confirm-Password">تأكيد كلمة المرور</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row ">
                        <div class="col-6">
                            <div class="form-floating form-floating-outline">
                                <select wire:model.defer='AdministratorRoles' id="AdministratorRoles"
                                    class="select2 form-select h-100" multiple data-placeholder="Select a Roles">
                                    @foreach ($Roles as $Role)
                                        @if (Auth::User()->hasRole('OWNER') or $Role->name != 'OWNER')
                                            <option value="{{ $Role->id }}">{{ $Role->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <label for="AdministratorRoles">الدور</label>
                            </div>
                            @error('AdministratorRoles')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="form-floating form-floating-outline">
                                    <select wire:model.defer="plan" class="form-control" id="AdministratorPlan">
                                        <option value=""></option>
                                        <option value="Supervisor">مشرف</option>
                                        <option value="Employee">موظف</option>
                                        <option value="Customer">عميل</option>
                                    </select>
                                    <label id="AdministratorPlan">خطة العمل</label>
                                    @error('plan')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-2 form-floating form-floating-outline">
                                <select wire:model.defer="status" class="form-control mt-3" id="AdministratorStatus">
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

                    <hr class="text-primary mt-n2">

                    <div class="text-center col-12 mb-n4">
                        <button wire:click='store' type="submit" class="btn btn-primary me-sm-3 me-1">اضافة
                            مشرف</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                            aria-label="Close">تجاهل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Add Administrator Modal -->
