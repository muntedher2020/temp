<!-- Remove Permission Modal -->
<div wire:ignore.self class="modal fade" id="removePermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="text-center mb-4">
                    <h3 class="fw-bold mb-2">
                        <span class="text-danger">حذف</span> التصريح
                    </h3>
                    <p class="text-muted">
                        <i class="mdi mdi-delete-outline me-1"></i>
                        هل أنت متأكد من حذف هذا التصريح؟
                    </p>
                </div>
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading fw-bold mb-2">تحذير</h6>
                    <p class="mb-0">من خلال حذف التصريح ، قد تكسر وظائف تصاريح النظام. يرجى التأكد من أنك متأكد تمامًا
                        قبل المتابعة.</p>
                </div>
                <form id="editPermissionForm" class="row" onsubmit="return false">
                    <div class="mb-3 col-12">
                        <div class="p-3 border rounded">
                            <div class="row mb-2">
                                <div class="col-4">
                                    <label class="text-muted">اسم التصريح:</label>
                                </div>
                                <div class="col-8">
                                    <span class="text-danger fw-bold">{{ $name }}</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <label class="text-muted">شرح التصريح:</label>
                                </div>
                                <div class="col-8">
                                    <span class="text-danger">{{ $explain_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="mb-n2">

                    <div class="text-center col-12 d-flex gap-2">
                        <button wire:click='destroy' type="submit" class="btn btn-danger w-50">حذف التصريح</button>
                        <button type="reset" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal" aria-label="Close">تجاهل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Edit Permission Modal -->
