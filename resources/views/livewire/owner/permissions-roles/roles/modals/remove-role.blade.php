<!-- Remove Role Modal -->
<div wire:ignore.self class="modal fade" id="removeRoleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-add-new-role">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center">
                    <h3 class="role-title fw-bold mb-2 text-danger">
                        <i class="mdi mdi-delete me-2"></i>حذف الدور
                    </h3>
                    <p class="text-muted">
                        <span class="badge bg-label-danger">سيتم حذف الدور وجميع الصلاحيات المرتبطة به</span>
                    </p>
                </div>
                <!-- Remove role form -->
                <form id="removeRoleForm" class="row g-3" onsubmit="return false">
                    <!-- Role Permissions Section -->
                    <div class="mb-4">
                        <div class="alert alert-danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Role Name Section -->
                                @if (Auth::User()->hasRole('OWNER'))
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-account-circle-outline fs-3 me-2"></i>
                                        <strong class="me-2">أسم الدور:</strong>
                                        <span class="text-danger">{{ $name ?? '' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row">
                            @foreach ($Permissions as $Permission)
                                <div class="mb-3 col-3">
                                    <label class="switch switch-square">
                                        <input wire:model.defer='SetPermission.{{ $Permission->id }}' type="checkbox"
                                            value="{{ $Permission->name }}" class="switch-input" disabled />
                                        <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                        </span>
                                        <div class="d-flex flex-column">
                                            <span class="switch-label text-dark fw-bolder">{{ $Permission->name }}</span>
                                            <span class="switch-label text-muted">{{ $Permission->explain_name }}</span>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-center col-12">
                        <hr>
                        <button wire:click='destroy' type="button" class="btn btn-danger me-sm-3 me-1">حذف الدور</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                            aria-label="Close">تجاهل</button>
                    </div>
                </form>
                <!--/ Remove role form -->
            </div>
        </div>
    </div>
</div>
<!--/ Remove Role Modal -->
