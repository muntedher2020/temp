<!-- Edit Permission Modal -->
<div wire:ignore.self class="modal fade" id="editPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-warning">تحرير</span> التصريح
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-shield-edit-outline me-1"></i>
                            تعديل التصريح وفقا لمتطلباتك
                        </p>
                    </div>
                </div>

                <div class="alert alert-warning" role="alert">
                    <h6 class="mb-2 alert-heading fw-bold">تحذير</h6>
                    <p class="mb-0">من خلال تحرير اسم التصريح ، قد تكسر وظائف تصاريح النظام. يرجى التأكد من أنك متأكد تمامًا قبل المتابعة.</p>
                </div>

                <hr class="mt-n2">

                <form id="editPermissionForm" class="row" onsubmit="return false">
                    <div class="mb-3 col-6">
                        <div class="form-floating form-floating-outline">
                            <input wire:model='name' type="text" id="editPermissionName" placeholder="اسم التصريح"
                                class="form-control @if(strlen($name) > 0) is-filled @endif @error('name') is-invalid is-filled @enderror"/>
                            <label for="editPermissionName">اسم التصريح</label>
                        </div>
                        @error('name')
                            <small class='text-danger inputerror'> {{ $message }} </small>
                        @enderror
                    </div>
                    <div class="mb-3 col-6">
                        <div class="form-floating form-floating-outline">
                            <input wire:model='explain_name' type="text" id="editExplainName" placeholder="شرح التصريح"
                                class="form-control @if(strlen($explain_name) > 0) is-filled @endif @error('explain_name') is-invalid is-filled @enderror"/>
                            <label for="editExplainName">شرح التصريح</label>
                        </div>
                        @error('explain_name')
                            <small class='text-danger inputerror'> {{ $message }} </small>
                        @enderror
                    </div>

                    <hr class="mb-n2">

                    <div class="text-center col-12 d-flex gap-2">
                        <button wire:click='update' type="submit" class="btn btn-warning w-50">تعديل التصريح</button>
                        <button type="reset" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal" aria-label="Close">تجاهل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Edit Permission Modal -->
