<!-- Add Role Modal -->
<div wire:ignore.self class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-add-new-role">
        <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center">
                    <h3 class="role-title fw-bold mb-2 text-primary">
                        <i class="mdi mdi-shield-plus me-2"></i>اضافة دور جديد
                    </h3>
                    <p class="text-muted">
                        <span class="badge bg-label-primary">قم بتعيين صلاحيات الدور من هنا</span>
                    </p>
                </div>
                <!-- Add role form -->
                <form id="addRoleForm" class="row g-3">
                    <!-- Role Permissions Section -->
                    <div class="mb-4">
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Role Name Section -->
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='name' type="text" id="RoleNameModal" name="RoleNameModal" autofocus
                                        class="form-control @if(strlen($name) > 0) is-filled @endif @error('name') is-invalid is-filled @enderror"
                                        placeholder="أسم الدور" tabindex="-1" />
                                    <label for="RoleNameModal">أسم الدور</label>
                                </div>
                                @error('name')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror

                                <!-- Permissions Control Section -->
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Admin Permissions Info -->
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">صلاحيات المسؤول</span>
                                        <i class="mdi mdi-information-outline text-primary"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="يسمح بالوصول الكامل إلى النظام">
                                        </i>
                                    </div>

                                    <!-- Select All Switch -->
                                    <label class="switch switch-square d-flex align-items-center mb-0">
                                        <input wire:click='CheckAll' type="checkbox" class="switch-input" id="selectAll"/>
                                        <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                        </span>
                                        <span class="switch-label ms-2">تحديد كل الأدوار</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- حقل البحث -->
                    <div class="mt-3">
                        <input type="text" wire:model="searchPermission" class="form-control"
                            placeholder="ابحث عن صلاحية...">
                    </div>

                    <div class="col-12">
                        <div class="row">
                            @foreach ($this->filteredPermissions as $Permission)
                                <div class="mb-3 col-3">
                                    <label class="switch switch-square">
                                        <input wire:model='SetPermission.{{ $Permission->id }}'
                                            wire:click="TickPermission"
                                            type="checkbox"
                                            value="{{ $Permission->name }}"
                                            class="switch-input" />
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
                            @error('SetPermission')
                                <small class='text-danger inputerror'> {{ $message }} </small>
                            @enderror
                        </div>
                    </div>
                    <div class="text-center col-12">
                        <hr>
                        <button wire:click='store' type="submit" class="btn btn-primary me-sm-3 me-1">اضافة الدور</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                            aria-label="Close">تجاهل</button>
                    </div>
                </form>
                <!--/ Add role form -->
            </div>
        </div>
    </div>
</div>
<!--/ Add Role Modal -->
