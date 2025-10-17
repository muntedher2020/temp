<!-- Show Administrator Modal -->
<div wire:ignore.self class="modal fade" id="ShowAdministrators" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="mb-4 text-center mt-n4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold mb-2">
                        <span class="text-primary">عرض</span> بيانات المشرف
                    </h3>
                    <p class="text-muted">
                        <i class="mdi mdi-account-details me-1"></i>
                        عرض بيانات المستخدم بصفة مشرف
                    </p>
                </div>
                <hr class="text-primary mt-n2">

                <h5 wire:loading wire:target="GetAdministratorsAccount"
                    wire:loading.class="d-flex justify-content-center text-primary">جار معالجة البيانات...</h5>

                <div wire:loading.remove>
                    <div class="row">
                        <div class="mb-3 col-4">
                            <div class="text-primary">
                                <label for="AdministratorName">أسم المشرف</label>
                                <div class="form-control-plaintext mt-n2">{{ $name }}</div>
                            </div>
                        </div>
                        <div class="mb-3 col-4">
                            <div class="text-primary">
                                <label for="AdministratorEmail">البريد الالكتروني</label>
                                <div class="form-control-plaintext mt-n2">{{ $email }}</div>
                            </div>
                        </div>
                        <div class="mb-3 col-4">
                            <div class="text-primary">
                                <label for="AdministratorRole" class="text-primary mb-n0">الدور</label>
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
                            <div class="text-primary">
                                <label for="AdministratorPlan">خطة العمل</label>
                                <div class="form-control-plaintext mt-n2">{{ $plan }}</div>
                            </div>
                        </div>

                        <div class="mb-3 col-6">
                            <div class="text-primary mt-3">
                                <label for="AdministratorStatus">حالة المشرف</label>
                                <div
                                    class="form-control-plaintext mt-n2 {{ $status ? 'text-success' : 'text-warning' }}">
                                    {{ $status ? 'مفعل' : 'غير مفعل' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="text-primary mt-n2">

            <div class="d-grid gap-2 mx-auto mb-n4">
                <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                    aria-label="Close">حسناً</button>
            </div>
        </div>
    </div>
</div>
<!--/ Show Administrator Modal -->
