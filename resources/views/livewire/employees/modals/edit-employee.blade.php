<!-- Edite Employee Modal -->
<div wire:ignore.self class="modal fade" id="editemployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-warning">تعديل</span> بيانات الموظفين
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-cog me-1"></i>
                            قم بتعديل تفاصيل الموظفين في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="update, GetEmployee">
                    <form id="editEmployeeModalForm" autocomplete="off">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input wire:model.defer='employee_name' type="text"
                                                id="modalEditEmployeeemployee_name" placeholder="اسم الموظف"
                                                class="form-control @error('employee_name') is-invalid is-filled @enderror" />
                                            <label for="modalEditEmployeeemployee_name">اسم الموظف</label>
                                        </div>
                                        @error('employee_name')
                                            <small class='text-danger inputerror'> {{ $message }} </small>
                                        @enderror
                                    </div>
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='gender'
                                            id="modalEditEmployeegender"
                                            class="form-select @error('gender') is-invalid is-filled @enderror">
                                            <option value="">اختر الجنس</option>
                                            <option value="ذكر">ذكر</option><option value="انثى">انثى</option>
                                        </select>
                                    </div>
                                    @error('gender')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='ed_level_id'
                                            id="modalEditEmployeeed_level_id"
                                            class="form-select @error('ed_level_id') is-invalid is-filled @enderror">
                                            <option value="">اختر التحصيل العلمي</option>
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
                                    </div>
                                    @error('ed_level_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='department_id'
                                            id="modalEditEmployeedepartment_id"
                                            class="form-select @error('department_id') is-invalid is-filled @enderror">
                                            <option value="">اختر القسم</option>
                                            @if(class_exists('App\Models\Departments\Departments'))
                                        @foreach(App\Models\Departments\Departments::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @elseif(class_exists('App\Models\Department\Department'))
                                        @foreach(App\Models\Department\Department::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @endif
                                        </select>
                                    </div>
                                    @error('department_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='job_title_id'
                                            id="modalEditEmployeejob_title_id"
                                            class="form-select @error('job_title_id') is-invalid is-filled @enderror">
                                            <option value="">اختر العنوان الوظيفي</option>
                                            @if(class_exists('App\Models\JobTitles\JobTitles'))
                                        @foreach(App\Models\JobTitles\JobTitles::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @elseif(class_exists('App\Models\JobTitle\JobTitle'))
                                        @foreach(App\Models\JobTitle\JobTitle::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @endif
                                        </select>
                                    </div>
                                    @error('job_title_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='job_grade_id'
                                            id="modalEditEmployeejob_grade_id"
                                            class="form-select @error('job_grade_id') is-invalid is-filled @enderror">
                                            <option value="">اختر الدرجة الوظيفية</option>
                                            @if(class_exists('App\Models\JobGrades\JobGrades'))
                                        @foreach(App\Models\JobGrades\JobGrades::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @elseif(class_exists('App\Models\JobGrade\JobGrade'))
                                        @foreach(App\Models\JobGrade\JobGrade::all() as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @endif
                                        </select>
                                    </div>
                                    @error('job_grade_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                        <div class="form-floating form-floating-outline">
                                            <textarea wire:model='notes'
                                                id="modalEditEmployeenotes" placeholder="ملاحظات"
                                                class="form-control h-px-100 @error('notes') is-invalid is-filled @enderror"></textarea>
                                            <label for="modalEditEmployeenotes">ملاحظات</label>
                                        </div>
                                        @error('notes')
                                            <small class='text-danger inputerror'> {{ $message }} </small>
                                        @enderror
                                    </div>
                        </div>
                        <hr class="my-0">
                        <div class="text-center col-12 demo-vertical-spacing mb-n4">
                            <button wire:click='update' wire:loading.attr="disabled" type="button"
                                class="btn btn-warning me-sm-3 me-1">تعديل</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">تجاهل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Edite Employee Modal -->
