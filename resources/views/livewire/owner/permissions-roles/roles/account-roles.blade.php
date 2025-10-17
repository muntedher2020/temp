<div class="mt-n4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="d-flex align-items-center gap-2">
                        <span class="text-muted d-flex align-items-center">
                            <i class="mdi mdi-account-group fs-4"></i>
                            <span class="ms-1">الأدوار</span>
                        </span>
                        <i class="mdi mdi-chevron-left text-primary"></i>
                        <span class="fw-bold text-primary d-flex align-items-center">
                            <i class="mdi mdi-account-key me-1 fs-4"></i>
                            <span class="ms-1">قائمة الأدوار</span>
                        </span>
                    </h4>
                    <p class="text-muted fs-6 fw-light lh-base mb-0">
                        <i class="mdi mdi-information-outline me-1"></i>
                        يوفر الدور الوصول إلى القوائم والميزات المحددة مسبقًا بحيث يمكن للمسؤول ، بناءً على الدور المعين ،
                        الوصول إلى ما يحتاجه المستخدم.
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Role cards -->
            <div class="row g-4">
                @foreach ($Roles as $Role)
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-2 d-flex justify-content-between">
                                    <h6 class="fw-normal">مستخدمي الدور</h6>
                                    @php
                                        $Users = App\Models\User::role($Role->name)->get()->pluck('name');
                                    @endphp
                                    <h2 class="fw-normal mt-n2">{{ $Users->count() }}</h2>
                                </div>
                                <div class="role-heading">
                                    <h4 class="mt-n3 fw-bolder">{{ $Role->name }}</h4>
                                </div>
                                <div class="mb-n3 d-flex justify-content-between">
                                    {{-- @can('role-edit') --}}
                                        <a href="javascript:void(0);" wire:click="GetRole({{ $Role->id }})"
                                            class="btn rounded-pill btn-text-primary waves-effect waves-light"
                                            data-bs-toggle="modal" data-bs-target="#editRoleModal">
                                            <span>تحرير الدور</span>
                                        </a>
                                    {{-- @endcan --}}
                                    @can('role-delete')
                                        @if (Auth::User()->hasRole('OWNER') and $Role->name != 'OWNER')
                                            <a href="javascript:void(0);" wire:click="GetRole({{ $Role->id }})"
                                                class="btn rounded-pill btn-text-danger waves-effect waves-light"
                                                data-bs-toggle="modal" data-bs-target="#removeRoleModal">
                                                <span>حذف الدور</span>
                                            </a>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100">
                        <div class="row h-100">
                            <div class="col-7">
                                <div class="text-center card-body text-sm-start ">
                                    <button wire:click="create" data-bs-target="#addRoleModal" data-bs-toggle="modal"
                                        class="mb-3 btn btn-primary text-nowrap add-new-role">اضافة دور</button>
                                    <p class="mb-0">أضف دورًا ، إذا لم يكن موجودًا</p>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="d-flex align-items-end h-100 justify-content-center pe-sm-0">
                                    <img src="{{ asset('assets/img/illustrations/add-new-role-illustration.png') }}"
                                        class="img-fluid" alt="Image" width="70">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 d-flex justify-content-center">
                    {{ $links->links() }}
                </div>
            </div>
            <!--/ Role cards -->
        </div>
    </div>

    <!-- Role Modal -->
    @include('livewire.owner.permissions-roles.roles.modals.add-role')
    @include('livewire.owner.permissions-roles.roles.modals.edit-role')
    @include('livewire.owner.permissions-roles.roles.modals.remove-role')
    <!-- / Role Modal -->
</div>
