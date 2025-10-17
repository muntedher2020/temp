<div class="mt-n4">
    @can('employee-view')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <div class="w-50">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb breadcrumb-style1 mb-0">
                                <li class="breadcrumb-item fs-4">
                                    <i class="mdi mdi-view-dashboard "></i>
                                    <a href="{{ route('Dashboard') }}">لوحة التحكم</a>
                                </li>
                                <li class="breadcrumb-item active fs-4">
                                    <span class="fw-bold text-primary d-flex align-items-center">
                                        <i class="mdi mdi-cog me-1 fs-4"></i>
                                        <span class="ms-1">الموظفين</span>
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <div class="d-flex gap-2">
                            <!-- Unified Dropdown for Export/Print options -->
                            @if(auth()->user()->can('employee-export-excel') || auth()->user()->can('employee-export-pdf'))
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-download me-1"></i>
                                        تصدير / طباعة
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('employee-export-excel')
                                            <li>
                                                <a class="dropdown-item" href="#" wire:click="exportSelected" {{ $selectedRows && count($selectedRows) > 0 ? '' : 'onclick="return false;"' }} style="{{ $selectedRows && count($selectedRows) > 0 ? '' : 'opacity: 0.5; cursor: not-allowed;' }}">
                                                    <i class="mdi mdi-file-excel me-2 text-success"></i>
                                                    تصدير Excel
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endcan
                                        @can('employee-export-pdf')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('Employees.export.pdf.tcpdf') }}">
                                                    <i class="mdi mdi-file-pdf-box me-2 text-danger"></i>
                                                    تصدير PDF (TCPDF)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('Employees.print.view') }}" target="_blank">
                                                    <i class="mdi mdi-printer me-2 text-info"></i>
                                                    طباعة مباشرة
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            @endif
                            @can('employee-create')
                                <button wire:click='AddEmployeeModalShow' class="mb-3 add-new btn btn-primary mb-md-0"
                                    data-bs-toggle="modal" data-bs-target="#addemployeeModal">أضــافــة</button>
                            @endcan
                        </div>
                        @include('livewire.employees.modals.add-employee')
                    </div>
                </div>
            </div>
            @can('employee-list')
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" wire:model="selectAll" id="selectAll">
                                    </div>
                                </th>
                                <th>#</th>
                                <th class="text-center">اسم الموظف</th>
                                <th class="text-center">الجنس</th>
                                <th class="text-center">التحصيل العلمي</th>
                                <th class="text-center">العملية</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">
                                        <input type="text" wire:model.debounce.300ms="search.employee_name"
                                            class="form-control text-center" placeholder="اسم الموظف"
                                            wire:key="search_employee_name">
                                    </th>
                                <th class="text-center">
                                        <select wire:model.debounce.300ms="search.gender"
                                            class="form-select text-center"
                                            wire:key="search_gender">
                                            <option value="">جميع الخيارات</option>
                                            <option value="ذكر">ذكر</option>
                                            <option value="انثى">انثى</option>
                                        </select>
                                    </th>
                                <th class="text-center">
                                        <select wire:model.debounce.300ms="search.ed_level_id"
                                            class="form-select text-center"
                                            wire:key="search_ed_level_id">
                                            <option value="">جميع الخيارات</option>
                                            @if(class_exists('App\Models\EducationalLevels\EducationalLevels'))
                                            @foreach(App\Models\EducationalLevels\EducationalLevels::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\EducationalLevel\EducationalLevel'))
                                            @foreach(App\Models\EducationalLevel\EducationalLevel::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @endif
                                        </select>
                                    </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = $links->perPage() * ($links->currentPage() - 1) + 1;
                            @endphp
                            @foreach ($Employees as $Employee)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" wire:model="selectedRows"
                                                value="{{ $Employee->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $i++ }}</td>
                                    <td class="text-center">{{$Employee->employee_name}}</td>
                                    <td class="text-center">{{$Employee->gender}}</td>
                                    <td class="text-center">
                                        @if($Employee->ed_level_id)
                                            @if(class_exists('App\Models\EducationalLevels\EducationalLevels'))
                                                {{ App\Models\EducationalLevels\EducationalLevels::find($Employee->ed_level_id)?->name ?? 'غير محدد' }}
                                            @elseif(class_exists('App\Models\EducationalLevel\EducationalLevel'))
                                                {{ App\Models\EducationalLevel\EducationalLevel::find($Employee->ed_level_id)?->name ?? 'غير محدد' }}
                                            @else
                                                {{ $Employee->ed_level_id }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="First group">
                                            @can('employee-edit')
                                                <button wire:click="GetEmployee({{$Employee->id}})"
                                                    class="p-0 px-1 btn btn-text-primary waves-effect" data-bs-toggle="modal"
                                                    data-bs-target="#editemployeeModal">
                                                    <i class="mdi mdi-text-box-edit-outline fs-3"></i>
                                                </button>
                                            @endcan
                                            @can('employee-delete')
                                                <strong style="margin: 0 10px;">|</strong>
                                                <button wire:click="GetEmployee({{$Employee->id}})"
                                                    class="p-0 px-1 btn btn-text-danger waves-effect"
                                                    data-bs-toggle = "modal" data-bs-target="#removeemployeeModal">
                                                    <i class="tf-icons mdi mdi-delete-outline fs-3"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-2 d-flex justify-content-center">
                        {{ $links->onEachSide(0)->links() }}
                    </div>
                </div>
                <!-- Modal -->
                @include('livewire.employees.modals.edit-employee')
                @include('livewire.employees.modals.remove-employee')
                <!-- Modal -->
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