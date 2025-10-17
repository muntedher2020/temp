<!-- Add Permission Modal -->
<div wire:ignore.self class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-primary">إضافة</span> تصريح جديد
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-shield-outline me-1"></i>
                            التصاريح التي يمكنك استخدامها وتعيينها للمستخدمين
                        </p>
                    </div>
                </div>

                <hr class="mt-n2">

                <form id="addPermissionForm" class="row">
                    <div class="mb-3 col-6">
                        <div class="form-floating form-floating-outline">
                            <input wire:model='name' type="text" id="modalPermissionName" placeholder="اسم التصريح"
                                autofocus
                                class="form-control @if (strlen($name) > 0) is-filled @endif @error('name') is-invalid is-filled @enderror" />
                            <label for="modalPermissionName">اسم التصريح</label>
                        </div>
                        @error('name')
                            <small class='text-danger inputerror'> {{ $message }} </small>
                        @enderror
                    </div>
                    <div class="mb-2 col-6">
                        <div class="form-floating form-floating-outline">
                            <input wire:model='explain_name' type="text" id="explain_name" placeholder="شرح التصريح"
                                class="form-control @if (strlen($explain_name) > 0) is-filled @endif @error('explain_name') is-invalid is-filled @enderror" />
                            <label for="explain_name">شرح التصريح</label>
                        </div>
                        @error('explain_name')
                            <small class='text-danger inputerror'> {{ $message }} </small>
                        @enderror
                    </div>

                    <hr class="my-0">
                    <div class="text-center col-12 d-flex gap-2">
                        <button wire:click='store' type="submit" class="btn btn-primary w-50">إنشاء التصريح</button>
                        <button type="reset" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal" aria-label="Close">تجاهل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Add Permission Modal -->
