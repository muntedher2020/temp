<!-- Remove Administrator Modal -->
<div wire:ignore.self class="modal fade" id="RmoveAdministrator" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="mb-4 text-center mt-n4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold mb-2">
                        <span class="text-danger">حذف</span> بيانات المشرف
                    </h3>
                    <p class="text-muted">
                        <i class="mdi mdi-account-remove me-1"></i>
                        حذف بيانات المستخدم بصفة مشرف
                    </p>
                </div>
                <hr class="text-danger mt-n2">

                <h5 wire:loading wire:target="GetAdministratorsAccount"
                    wire:loading.class="d-flex justify-content-center text-danger">جار معالجة البيانات...</h5>

                <div wire:loading.remove>
                    <div class="alert alert-danger {{ $status ? '' : 'hidden' }} {{-- alert-dismissible --}}" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="mdi mdi-alert-circle-outline fs-3"></i>
                            <h5 class="alert-heading mb-0">تنبيه الصلاحية</h5>
                        </div>
                        <hr class="my-2">
                        <p class="mb-0 text-wrap">
                            <i class="mdi mdi-information-outline me-1"></i>
                            يرجى إلغاء تفعيل حساب المشرف أولاً قبل إتمام عملية الحذف
                        </p>
                    </div>

                    <form id="removeAdministratorForm" class="pt-2 row" onsubmit="return false">
                        <div class="row">
                            <div class="mb-3 col-4">
                                <div class="text-danger">
                                    <label for="AdministratorName">أسم المشرف</label>
                                    <div class="form-control-plaintext mt-n2">{{ $name }}</div>
                                </div>
                            </div>
                            <div class="mb-3 col-4">
                                <div class="text-danger">
                                    <label for="AdministratorEmail">البريد الالكتروني</label>
                                    <div class="form-control-plaintext mt-n2">{{ $email }}</div>
                                </div>
                            </div>
                            <div class="mb-3 col-4">
                                <div class="text-danger">
                                    <label for="AdministratorRole" class="text-danger mb-n0">الدور</label>
                                    @if ($AdministratorAccount)
                                        @foreach ($AdministratorAccount->getRoleNames() as $RolesName)
                                            <div class="form-control-plaintext py-0">{{ $RolesName }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-6">
                                <div class="text-danger">
                                    <label for="AdministratorPlan">خطة العمل</label>
                                    <div class="form-control-plaintext mt-n2">{{ $plan }}</div>
                                </div>
                            </div>
                            <div class="mb-3 col-6">
                                <div class="text-danger">
                                    <label for="AdministratorStatus">حالة المستخدم</label>
                                    <div
                                        class="form-control-plaintext mt-n2 {{ $status ? 'text-success' : 'text-warning' }}">
                                        {{ $status ? 'مفعل' : 'غير مفعل' }}</div>
                                </div>
                            </div>
                        </div>

                        <hr class="text-danger mt-n2">

                        <div class="text-center col-12 mb-n4">
                            <button wire:click='destroy' {{ $status ? 'disabled' : '' }} type="submit"
                                class="btn btn-danger me-sm-3 me-1">حذف</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">تجاهل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Remove Administrator Modal -->
