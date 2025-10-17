<div class="mt-n4">
    @can('customer-view')
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
                                    <h5 class="mb-0">{{ $Users->count() }}</h5>
                                </div>
                                <small class="text-muted">العملاء</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="d-flex align-items-center gap-2">
                        <span class="text-muted d-flex align-items-center">
                            <i class="mdi mdi-account-group fs-4"></i>
                            <span class="ms-1">حسابات العملاء</span>
                        </span>
                        <i class="mdi mdi-chevron-left text-primary"></i>
                        <span class="fw-bold text-primary d-flex align-items-center">
                            <i class="mdi mdi-account-outline me-1 fs-4"></i>
                            <span class="ms-1">العملاء</span>
                        </span>
                    </h4>
                </div>
                <div>
                    @can('customer-create')
                        <button wire:click='UsersAccountAdd' class="mb-3 add-new btn btn-primary mb-md-0" data-bs-toggle="modal"
                            data-bs-target="#addCustomerModal">اضافة مستخدم</button>

                        @include('livewire.users.customers-accounts.modals.add-customer')
                    @endcan
                </div>
            </div>

            <div class="card-header mt-n4">
                <div class="row row-cols-1 row-cols-sm-1 row-cols-md-2 row-cols-lg-2 g-2">
                    <div class="col">
                        <input wire:model='SearchName' type="text" class="form-control" id="SearchName"
                            placeholder="الأسم">
                    </div>
                    <div class="col">
                        <input wire:model='SearchEmail' type="text" class="form-control" id="SearchEmail"
                            placeholder="البريد الالكتروني">
                    </div>
                </div>
            </div>
            @can('customer-list')
                <div class="card-datatable table-responsive">
                    <table class="{{-- datatables-users --}} table border">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">الاسم / البريد الألكتروني</th>
                                <th class="text-center">الدور</th>
                                <th class="text-center">تاريخ التسجيل</th>
                                <th class="text-center">حالة الحساب</th>
                                <th class="text-center">الاجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Users as $User)
                                <tr>
                                    <td class="text-center">
                                        {{ $User->name }}
                                        <div>
                                            <small>{{ $User->email }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $roles_count = count($User->getRoleNames());
                                            $i = 0;
                                            $disease = '';
                                        @endphp
                                        @foreach ($User->getRoleNames() as $roles)
                                            <?php $coma = '';
                                            $i++;
                                            if ($i < $roles_count) {
                                                $coma = ' , ';
                                            } ?>
                                            {{ $roles . $coma }}
                                        @endforeach
                                        <br>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            {{ $User->created_at }}
                                            <br>
                                            <span dir="ltr">
                                                {{ Carbon\Carbon::parse($User->created_at)->diffForHumans() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <!-- حالة الحساب -->
                                        <small class="{{ $User->account_status['class'] }}">
                                            {{ $User->account_status['text'] }}
                                        </small>

                                        <!-- حالة الاتصال -->
                                        @if ($User->status)
                                            <br>
                                            <small class="{{ $User->connection_status['class'] }}">
                                                {{ $User->connection_status['text'] }}
                                            </small>
                                        @endif

                                        <!-- آخر نشاط -->
                                        <div>
                                            <small class="text-muted">
                                                @if ($User->last_seen)
                                                    <span dir="ltr">{{ $User->last_seen->diffForHumans() }}</span>
                                                @else
                                                    لم يظهر أبداً
                                                @endif
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::User()->hasRole('OWNER') or !in_array('OWNER', $User->getRoleNames()->toArray()))
                                            <div class="btn-group" role="group" aria-label="First group">
                                                @can('customer-show')
                                                    <button wire:click='GetUsersAccount({{ $User->id }})' type="button"
                                                        class="p-0 px-1 btn btn-outline-primary waves-effect rounded-circle"
                                                        data-bs-toggle="modal" data-bs-target="#ShowCustomerModal">
                                                        <i class="tf-icons mdi mdi-account-eye-outline fs-3"></i>
                                                    </button>
                                                @endcan
                                                @can('customer-edit')
                                                    <button wire:click='GetUsersAccount({{ $User->id }})' type="button"
                                                        class="p-0 px-1 btn btn-outline-success waves-effect rounded-circle"
                                                        data-bs-toggle="modal" data-bs-target="#EditCustomerModal">
                                                        <i class="tf-icons mdi mdi-account-edit-outline fs-3"></i>
                                                    </button>
                                                @endcan

                                                @if (Auth::User()->hasRole('OWNER') and !in_array('OWNER', $User->getRoleNames()->toArray()))
                                                    @can('customer-delete')
                                                        <button wire:click='GetUsersAccount({{ $User->id }})' type="button"
                                                            class="p-0 px-1 btn btn-outline-danger waves-effect rounded-circle"
                                                            data-bs-toggle="modal" data-bs-target="#RmoveCustomerModal">
                                                            <i class="tf-icons mdi mdi-account-remove-outline fs-3"></i>
                                                        </button>
                                                    @endcan
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
                @include('livewire.users.customers-accounts.modals.show-customer')
                @include('livewire.users.customers-accounts.modals.edit-customer')
                @include('livewire.users.customers-accounts.modals.remove-customer')
                <!-- / Administrators Modal -->
            @endcan
        </div>
    @else
        <div class="container-xxl">
            <div class="misc-wrapper">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="mdi mdi-shield-lock-outline text-primary fs-1" style="opacity: 0.9;"></i>
                        </div>
                        <h2 class="mb-3 fw-semibold">عذراً! ليس لديك صلاحيات الوصول</h2>
                        <p class="mb-4 mx-auto text-muted" style="max-width: 500px;">
                            لا تملك الصلاحيات الكافية للوصول إلى هذه الصفحة. يرجى التواصل مع مدير النظام للحصول على
                            المساعدة.
                        </p>
                        <a href="{{ route('Dashboard') }}"
                            class="btn btn-primary btn-lg rounded-pill px-5 waves-effect waves-light">
                            <i class="mdi mdi-home-outline me-1"></i>
                            العودة إلى الرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
