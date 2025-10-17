<div class="mt-n4">
    @can('trainer-view')
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
                                        <span class="ms-1">المدربين</span>
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <div class="d-flex gap-2">
                            <!-- Unified Dropdown for Export/Print options -->
                            @if(auth()->user()->can('trainer-export-excel') || auth()->user()->can('trainer-export-pdf'))
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-download me-1"></i>
                                        تصدير / طباعة
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('trainer-export-excel')
                                            <li>
                                                <a class="dropdown-item" href="#" wire:click="exportSelected" {{ $selectedRows && count($selectedRows) > 0 ? '' : 'onclick="return false;"' }} style="{{ $selectedRows && count($selectedRows) > 0 ? '' : 'opacity: 0.5; cursor: not-allowed;' }}">
                                                    <i class="mdi mdi-file-excel me-2 text-success"></i>
                                                    تصدير Excel
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endcan
                                        @can('trainer-export-pdf')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('Trainers.export.pdf.tcpdf') }}">
                                                    <i class="mdi mdi-file-pdf-box me-2 text-danger"></i>
                                                    تصدير PDF (TCPDF)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('Trainers.print.view') }}" target="_blank">
                                                    <i class="mdi mdi-printer me-2 text-info"></i>
                                                    طباعة مباشرة
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            @endif
                            @can('trainer-create')
                                <button wire:click='AddTrainerModalShow' class="mb-3 add-new btn btn-primary mb-md-0"
                                    data-bs-toggle="modal" data-bs-target="#addtrainerModal">أضــافــة</button>
                            @endcan
                        </div>
                        @include('livewire.trainers.modals.add-trainer')
                    </div>
                </div>
            </div>
            @can('trainer-list')
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
                                <th class="text-center">اسم المدرب</th>
                                <th class="text-center">مؤسسة المدرب</th>
                                <th class="text-center">التحصيل العلمي</th>
                                <th class="text-center">المجال التدريبي</th>
                                <th class="text-center">العملية</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">
                                        <input type="text" wire:model.debounce.300ms="search.trainer_name"
                                            class="form-control text-center" placeholder="اسم المدرب"
                                            wire:key="search_trainer_name">
                                    </th>
                                <th class="text-center">
                                        <select wire:model.debounce.300ms="search.institution_id"
                                            class="form-select text-center"
                                            wire:key="search_institution_id">
                                            <option value="">جميع الخيارات</option>
                                            @if(class_exists('App\Models\TrainingInstitutions\TrainingInstitutions'))
                                            @foreach(App\Models\TrainingInstitutions\TrainingInstitutions::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\TrainingInstitution\TrainingInstitution'))
                                            @foreach(App\Models\TrainingInstitution\TrainingInstitution::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @endif
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
                                <th class="text-center">
                                        <select wire:model.debounce.300ms="search.domain_id"
                                            class="form-select text-center"
                                            wire:key="search_domain_id">
                                            <option value="">جميع الخيارات</option>
                                            @if(class_exists('App\Models\TrainingDomains\TrainingDomains'))
                                            @foreach(App\Models\TrainingDomains\TrainingDomains::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\TrainingDomain\TrainingDomain'))
                                            @foreach(App\Models\TrainingDomain\TrainingDomain::all() as $item)
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
                            @foreach ($Trainers as $Trainer)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" wire:model="selectedRows"
                                                value="{{ $Trainer->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $i++ }}</td>
                                    <td class="text-center">{{$Trainer->trainer_name}}</td>
                                    <td class="text-center">
                                        @if($Trainer->institution_id)
                                            @if(class_exists('App\Models\TrainingInstitutions\TrainingInstitutions'))
                                                {{ App\Models\TrainingInstitutions\TrainingInstitutions::find($Trainer->institution_id)?->name ?? 'غير محدد' }}
                                            @elseif(class_exists('App\Models\TrainingInstitution\TrainingInstitution'))
                                                {{ App\Models\TrainingInstitution\TrainingInstitution::find($Trainer->institution_id)?->name ?? 'غير محدد' }}
                                            @else
                                                {{ $Trainer->institution_id }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($Trainer->ed_level_id)
                                            @if(class_exists('App\Models\EducationalLevels\EducationalLevels'))
                                                {{ App\Models\EducationalLevels\EducationalLevels::find($Trainer->ed_level_id)?->name ?? 'غير محدد' }}
                                            @elseif(class_exists('App\Models\EducationalLevel\EducationalLevel'))
                                                {{ App\Models\EducationalLevel\EducationalLevel::find($Trainer->ed_level_id)?->name ?? 'غير محدد' }}
                                            @else
                                                {{ $Trainer->ed_level_id }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($Trainer->domain_id)
                                            @if(class_exists('App\Models\TrainingDomains\TrainingDomains'))
                                                {{ App\Models\TrainingDomains\TrainingDomains::find($Trainer->domain_id)?->name ?? 'غير محدد' }}
                                            @elseif(class_exists('App\Models\TrainingDomain\TrainingDomain'))
                                                {{ App\Models\TrainingDomain\TrainingDomain::find($Trainer->domain_id)?->name ?? 'غير محدد' }}
                                            @else
                                                {{ $Trainer->domain_id }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="First group">
                                            @can('trainer-edit')
                                                <button wire:click="GetTrainer({{$Trainer->id}})"
                                                    class="p-0 px-1 btn btn-text-primary waves-effect" data-bs-toggle="modal"
                                                    data-bs-target="#edittrainerModal">
                                                    <i class="mdi mdi-text-box-edit-outline fs-3"></i>
                                                </button>
                                            @endcan
                                            @can('trainer-delete')
                                                <strong style="margin: 0 10px;">|</strong>
                                                <button wire:click="GetTrainer({{$Trainer->id}})"
                                                    class="p-0 px-1 btn btn-text-danger waves-effect"
                                                    data-bs-toggle = "modal" data-bs-target="#removetrainerModal">
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
                @include('livewire.trainers.modals.edit-trainer')
                @include('livewire.trainers.modals.remove-trainer')
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