<div class="mt-n4">
    @can('user-view')
        <div class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <div class="card bg-primary-subtle mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar">
                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                            style="width: 48px; height: 48px">
                                            <i class="mdi mdi-shield-account mdi-24px text-white"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h4 class="mb-0">{{ $Administrators->count() }}</h4>
                                        <small class="text-muted">إجمالي المشرفين</small>
                                    </div>
                                </div>
                                <div class="vr d-none d-sm-block" style="height: 40px"></div>
                                <div class="d-flex flex-wrap gap-3 ms-auto">
                                    @if (Auth::User()->hasRole('OWNER'))
                                        <div class="text-center">
                                            <h5 class="mb-1">
                                                {{ App\Models\User::whereIn('plan', ['Supervisor', 'Employee'])->with('roles')->get()->filter(fn($user) => $user->roles->where('name', 'OWNER')->toArray())->count() }}
                                            </h5>
                                            <span class="badge bg-primary">OWNER</span>
                                        </div>
                                    @endif
                                    <div class="text-center">
                                        <h5 class="mb-1">{{ count(App\Models\User::role('Administrator')->get()) }}</h5>
                                        <span class="badge bg-info">Administrator</span>
                                    </div>
                                    <div class="text-center">
                                        <h5 class="mb-1">
                                            {{ count(App\Models\User::role('Supervisor')->get()->pluck('name')) }}</h5>
                                        <span class="badge bg-warning">Supervisor</span>
                                    </div>
                                    <div class="text-center">
                                        <h5 class="mb-1">
                                            {{ count(App\Models\User::role('Employee')->get()->pluck('name')) }}</h5>
                                        <span class="badge bg-success">Employee</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary-subtle mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar">
                                        <div class="rounded-circle bg-warning-subtle p-2">
                                            <i class="mdi mdi-account-lock mdi-24px text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h5 class="mb-1">{{ $Administrators->where('plan', 'Supervisor')->count() }}</h5>
                                        <span class="badge bg-warning">Supervisor</span>
                                    </div>
                                </div>
                                <div class="vr mx-3" style="height: 48px"></div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar">
                                        <div class="rounded-circle bg-success-subtle p-2">
                                            <i class="mdi mdi-account-tie mdi-24px text-success"></i>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h5 class="mb-1">{{ $Administrators->where('plan', 'Employee')->count() }}</h5>
                                        <span class="badge bg-success">Employee</span>
                                    </div>
                                </div>
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
                            <i class="mdi mdi-cog-outline fs-4"></i>
                            <span class="ms-1">حسابات المشرفين</span>
                        </span>
                        <i class="mdi mdi-chevron-left text-primary"></i>
                        <span class="fw-bold text-primary d-flex align-items-center">
                            <i class="mdi mdi-shield-account me-1 fs-4"></i>
                            <span class="ms-1">مشرفي النظام</span>
                        </span>
                    </h4>
                </div>
                <div>
                    @can('user-create')
                        <button wire:click='AdministratorsAccountAdd' class="mb-3 add-new btn btn-primary mb-md-0"
                            data-bs-toggle="modal" data-bs-target="#addAdministratorModal">أضف مشرفاً</button>

                        @include('livewire.users.administrators-accounts.modals.add-administrator')
                    @endcan
                </div>
            </div>

            <div class="card-header d-flex align-items-center mt-n4">
                <div class="row w-100">
                    <div class="mb-3 col">
                        <input type="text" wire:model.debounce.300ms="search.name" class="form-control text-center"
                            placeholder="أسم المشرف" wire:key="search_name">
                    </div>
                    <div class="mb-3 col">
                        <input type="text" wire:model.debounce.300ms="search.email" class="form-control text-center"
                            placeholder="البريد الالكتروني" wire:key="search_email">
                    </div>
                    <div class="mb-3 col">
                        <select wire:model.debounce.300ms="search.role" class="form-select text-center"
                            wire:key="search_role">
                            <option value="">الصلاحية</option>
                            @foreach ($Roles as $Role)
                                <option value="{{ $Role->id }}">
                                    {{ $Role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col">
                        <select wire:model.debounce.300ms="search.status" class="form-select text-center"
                            wire:key="search_status">
                            <option value="">كل الحالات</option>
                            <option value="1">مفعل</option>
                            <option value="0">غير مفعل</option>
                            <option value="متصل">متصل</option>
                            <option value="غير متصل">غير متصل</option>
                            <option value="مفعل - متصل">مفعل - متصل</option>
                            <option value="مفعل - غير متصل">مفعل - غير متصل</option>
                        </select>
                    </div>
                </div>
            </div>


            @can('user-list')
                <div class="card-datatable table-responsive">
                    <table class="{{-- datatables-users --}} table border">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">الاسم</th>
                                <th class="text-center">الدور / خطة العمل</th>
                                <th class="text-center">تاريخ التسجيل</th>
                                <th class="text-center">حالة الحساب</th>
                                <th class="text-center">الاجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Administrators as $Administrator)
                                <tr>
                                    <td class="text-center">
                                        {{ $Administrator->name }}
                                        <div>
                                            <small>{{ $Administrator->email }}</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $roles_count = count($Administrator->getRoleNames());
                                            $i = 0;
                                            $disease = '';
                                        @endphp
                                        @foreach ($Administrator->getRoleNames() as $roles)
                                            <?php $coma = '';
                                            $i++;
                                            if ($i < $roles_count) {
                                                $coma = ' , ';
                                            } ?>
                                            {{ $roles . $coma }}
                                        @endforeach
                                        <div>{{ $Administrator->plan }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            {{ $Administrator->created_at }}
                                            <br>
                                            <span dir="ltr">
                                                {{ Carbon\Carbon::parse($Administrator->created_at)->diffForHumans() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <!-- حالة الحساب -->
                                        <small class="{{ $Administrator->status ? 'text-success' : 'text-danger' }}">
                                            {{ $Administrator->status ? 'مفعل' : 'غير مفعل' }}
                                        </small>

                                        <!-- حالة الاتصال -->
                                        @if ($Administrator->status)
                                            <br>
                                            <small class="{{ $Administrator->isOnline() ? 'text-success' : 'text-danger' }}">
                                                {{ $Administrator->isOnline() ? 'متصل' : 'غير متصل' }}
                                            </small>
                                        @endif

                                        <!-- آخر نشاط -->
                                        <div>
                                            @if ($Administrator->last_seen)
                                                <span dir="ltr">{{ $Administrator->last_seen->diffForHumans() }}</span>
                                            @else
                                                <small>لم يظهر أبداً</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::User()->hasRole('OWNER') or !in_array('OWNER', $Administrator->getRoleNames()->toArray()))
                                            <div class="btn-group" role="group" aria-label="First group">
                                                @can('user-show')
                                                    <button wire:click='GetAdministratorsAccount({{ $Administrator->id }})'
                                                        type="button"
                                                        class="p-0 px-1 btn btn-outline-primary waves-effect rounded-circle"
                                                        data-bs-toggle="modal" data-bs-target="#ShowAdministrators">
                                                        <i class="tf-icons mdi mdi-account-eye-outline fs-3"></i>
                                                    </button>
                                                @endcan
                                                @if (Auth::User()->id == $Administrator->id or !in_array('OWNER', $Administrator->getRoleNames()->toArray()))
                                                    @can('user-edit')
                                                        <button wire:click='GetAdministratorsAccount({{ $Administrator->id }})'
                                                            type="button"
                                                            class="p-0 px-1 btn btn-outline-success waves-effect rounded-circle"
                                                            data-bs-toggle="modal" data-bs-target="#EditAdministrator">
                                                            <i class="tf-icons mdi mdi-account-edit-outline fs-3"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                                @if (Auth::User()->hasRole('OWNER') and !in_array('OWNER', $Administrator->getRoleNames()->toArray()))
                                                    @can('user-delete')
                                                        <button wire:click='GetAdministratorsAccount({{ $Administrator->id }})'
                                                            type="button"
                                                            class="p-0 px-1 btn btn-outline-danger waves-effect rounded-circle"
                                                            data-bs-toggle="modal" data-bs-target="#RmoveAdministrator">
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
                @include('livewire.users.administrators-accounts.modals.show-administrator')
                @include('livewire.users.administrators-accounts.modals.edit-administrator')
                @include('livewire.users.administrators-accounts.modals.remove-administrator')
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
