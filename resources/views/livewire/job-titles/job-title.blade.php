<div class="mt-n4">
    @can('jobtitle-view')
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
                                        <span class="ms-1">العنوان الوظيفي</span>
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <div class="d-flex gap-2">
                            <!-- Unified Dropdown for Export/Print options -->
                            @if(auth()->user()->can('jobtitle-export-excel') || auth()->user()->can('jobtitle-export-pdf'))
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-download me-1"></i>
                                        تصدير / طباعة
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('jobtitle-export-excel')
                                            <li>
                                                <a class="dropdown-item" href="#" wire:click="exportSelected" {{ $selectedRows && count($selectedRows) > 0 ? '' : 'onclick="return false;"' }} style="{{ $selectedRows && count($selectedRows) > 0 ? '' : 'opacity: 0.5; cursor: not-allowed;' }}">
                                                    <i class="mdi mdi-file-excel me-2 text-success"></i>
                                                    تصدير Excel
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endcan
                                        @can('jobtitle-export-pdf')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('JobTitles.export.pdf.tcpdf') }}">
                                                    <i class="mdi mdi-file-pdf-box me-2 text-danger"></i>
                                                    تصدير PDF (TCPDF)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('JobTitles.print.view') }}" target="_blank">
                                                    <i class="mdi mdi-printer me-2 text-info"></i>
                                                    طباعة مباشرة
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            @endif
                            @can('jobtitle-create')
                                <button wire:click='AddJobTitleModalShow' class="mb-3 add-new btn btn-primary mb-md-0"
                                    data-bs-toggle="modal" data-bs-target="#addjobtitleModal">أضــافــة</button>
                            @endcan
                        </div>
                        @include('livewire.job-titles.modals.add-job-title')
                    </div>
                </div>
            </div>
            @can('jobtitle-list')
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
                                <th class="text-center">العنوان الوظيفي</th>
                                <th class="text-center">العملية</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">
                                        <input type="text" wire:model.debounce.300ms="search.name"
                                            class="form-control text-center" placeholder="العنوان الوظيفي"
                                            wire:key="search_name">
                                    </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = $links->perPage() * ($links->currentPage() - 1) + 1;
                            @endphp
                            @foreach ($JobTitles as $JobTitle)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" wire:model="selectedRows"
                                                value="{{ $JobTitle->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $i++ }}</td>
                                    <td class="text-center">{{$JobTitle->name}}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="First group">
                                            @can('jobtitle-edit')
                                                <button wire:click="GetJobTitle({{$JobTitle->id}})"
                                                    class="p-0 px-1 btn btn-text-primary waves-effect" data-bs-toggle="modal"
                                                    data-bs-target="#editjobtitleModal">
                                                    <i class="mdi mdi-text-box-edit-outline fs-3"></i>
                                                </button>
                                            @endcan
                                            @can('jobtitle-delete')
                                                <strong style="margin: 0 10px;">|</strong>
                                                <button wire:click="GetJobTitle({{$JobTitle->id}})"
                                                    class="p-0 px-1 btn btn-text-danger waves-effect"
                                                    data-bs-toggle = "modal" data-bs-target="#removejobtitleModal">
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
                @include('livewire.job-titles.modals.edit-job-title')
                @include('livewire.job-titles.modals.remove-job-title')
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