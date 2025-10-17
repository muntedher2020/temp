<div class="mt-n4">
    <div class="mb-4 row g-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="rounded avatar-initial bg-label-primary">
                                <div class="mdi mdi-account-outline mdi-24px"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0">8,458</h5>
                                <div class="mdi mdi-chevron-down text-danger mdi-24px"></div>
                                <small class="text-danger">8.1%</small>
                            </div>
                            <small class="text-muted">New Customers</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="rounded avatar-initial bg-label-warning">
                                <div class="mdi mdi-poll mdi-24px"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0">$28.5K</h5>
                                <div class="mdi mdi-chevron-up text-success mdi-24px"></div>
                                <small class="text-success">18.2%</small>
                            </div>
                            <small class="text-muted">Total Profit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="rounded avatar-initial bg-label-info">
                                <div class="mdi mdi-trending-up mdi-24px"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0">2,450K</h5>
                                <div class="mdi mdi-chevron-down text-danger mdi-24px"></div>
                                <small class="text-danger">24.6%</small>
                            </div>
                            <small class="text-muted">New Transaction</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="rounded avatar-initial bg-label-success">
                                <div class="mdi mdi-currency-usd mdi-24px"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0">$48.2K</h5>
                                <div class="mdi mdi-chevron-down text-success mdi-24px"></div>
                                <small class="text-success">22.5%</small>
                            </div>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Users List Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">مستخدمي النظام</h5>
            <div>
               @can('user-create')
                    <button wire:click='UsersAccountAdd' class="mb-3 add-new btn btn-primary mb-md-0"
                        data-bs-toggle="modal" data-bs-target="#addUserModal">اضافة مستخدم</button>

                        @include('livewire.users.users-accounts.modals.add-user')
                @endcan
            </div>
            {{-- <div class="gap-3 py-3 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 user_role"></div>
                <div class="col-md-4 user_plan"></div>
                <div class="col-md-4 user_status"></div>
            </div> --}}
        </div>

        <div class="card-header d-flex align-items-center mt-n4">
            <div class="col">
                <input wire:model='SearchName' type="text" class="form-control" id="SearchName" placeholder="أسم {{ Auth::User()->plan }}">
            </div>
            <div class="col">
                <input wire:model='SearchEmail' type="text" class="form-control" id="SearchEmail" placeholder="البريد الالكتروني">
            </div>
            <div class="col">
                <input wire:model='SearchStore' type="text" class="form-control" id="SearchStore" placeholder="المحل">
            </div>
            <div class="col">
                <select class="form-select" id="SearchRole">
                    <option value="0" {{ 0 == $RoleSelect ? "selected" : '' }}>اظهار الكل</option>
                    @foreach ($Roles as $Role)
                        <option value="{{ $Role->id }}" {{ $Role->id == $RoleSelect?"selected":'' }}>{{ $Role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col">
                <select class="form-select" id="SearchStatus">
                    <option value="0" {{ 0 == $StatusSelect?"selected":'' }}>اظهار الكل</option>
                    <option value="مفعل" {{ 'مفعل' == $StatusSelect?"selected":'' }}>مفعل </option>
                    <option value="غير مفعل" {{ 'غير مفعل' == $StatusSelect?"selected":'' }}>غير مفعل</option>
                    {{-- <option value="متصل" {{ 'متصل' == $StatusSelect?"selected":'' }}>متصل </option>
                    <option value="غير متصل" {{ 'غير متصل' == $StatusSelect?"selected":'' }}>غير متصل</option>
                    <option value="مفعل - متصل" {{ 'مفعل - متصل' == $StatusSelect?"selected":'' }}>مفعل - متصل</option>
                    <option value="مفعل - غير متصل" {{ 'مفعل - غير متصل' == $StatusSelect?"selected":'' }}>مفعل - غير متصل</option> --}}
                </select>
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table class="{{-- datatables-users  --}}table border">
                <thead class="table-light">
                    <tr>
                        <th>الاسم / البريد الألكتروني</th>
                        <th>الدور</th>
                        <th>المحل</th>
                        <th>تاريخ التسجيل</th>
                        <th>حلة الحساب</th>
                        <th class="text-center">الاجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Users as $User)
                        <tr>
                            <td>
                                {{ $User->name }}
                                <div>
                                    <small>{{ $User->email }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    $roles_count = count($User->getRoleNames());
                                    $i = 0;
                                    $disease = '';
                                @endphp
                                @foreach($User->getRoleNames() as $roles)
                                    <?php $coma = ''; $i++; if($i < $roles_count) { $coma = ' , '; } ?>
                                    {{$roles .$coma }}
                                @endforeach<br>
                            </td>
                            <td>
                                {{ $User->GetStore ? $User->GetStore->name:'' }}
                            </td>
                            <td>
                                {{ $User->created_at }}
                                <div dir="ltr">
                                    {{ Carbon\Carbon::parse($User->created_at)->diffForHumans() }}
                                </div>
                            </td>
                            <td>
                                @php $Status = 'text-dark'; @endphp
                                @if ($User->status == 'مفعل')
                                    @php $Status = 'text-success'; @endphp
                                @else
                                    @php $Status = 'text-danger'; @endphp
                                @endif
                                <small class="{{ $Status }}">{{ $User->status }}</small>

                                @if (Cache::has('user-online' . $User->id))
                                    <small class="text-success">متصل</small>
                                @else
                                    <small class="text-danger">غير متصل</small>
                                @endif
                                <div>
                                    @if ($User->last_seen != null)
                                        <span class="" dir="ltr">{{ Carbon\Carbon::parse($User->last_seen)->diffForHumans() }}</span>
                                    @else
                                        <small>لم يظهر ابداً</small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if (Auth::User()->hasRole('OWNER') OR !in_array('OWNER', $User->getRoleNames()->toArray()) )
                                    <div class="btn-group" role="group" aria-label="First group">
                                        <button wire:click='GetUsersAccount({{ $User->id }})' type="button" class="p-0 px-1 btn btn-outline-primary waves-effect rounded-circle" data-bs-toggle="modal" data-bs-target="#ShowUserModal">
                                            <i class="tf-icons mdi mdi-account-eye-outline fs-3"></i>
                                        </button>
                                        <button wire:click='GetUsersAccount({{ $User->id }})' type="button" class="p-0 px-1 btn btn-outline-success waves-effect rounded-circle" data-bs-toggle="modal" data-bs-target="#EditUserModal">
                                            <i class="tf-icons mdi mdi-account-edit-outline fs-3"></i>
                                        </button>
                                        @if (Auth::User()->hasRole('OWNER') AND !in_array('OWNER', $User->getRoleNames()->toArray()))
                                            <button wire:click='GetUsersAccount({{ $User->id }})' type="button" class="p-0 px-1 btn btn-outline-danger waves-effect rounded-circle" data-bs-toggle="modal" data-bs-target="#RmoveUserModal">
                                                <i class="tf-icons mdi mdi-account-remove-outline fs-3"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-2 d-flex justify-content-center">
            {{ $links->links() }}
        </div>

        <!-- Administrators Modal -->
        @include('livewire.users.users-accounts.modals.show-user')
        @include('livewire.users.users-accounts.modals.edit-user')
        @include('livewire.users.users-accounts.modals.remove-user')
        <!-- / Administrators Modal -->

    </div>
</div>
