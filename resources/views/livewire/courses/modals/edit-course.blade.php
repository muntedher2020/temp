<!-- Edite Course Modal -->
<div wire:ignore.self class="modal fade" id="editcourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-warning">تعديل</span> بيانات الدورات التدريبية
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-cog me-1"></i>
                            قم بتعديل تفاصيل الدورات التدريبية في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="update, GetCourse">
                    <form id="editCourseModalForm" autocomplete="off">
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='course_title' type="text"
                                        id="modalEditCoursecourse_title" placeholder="عنوان الدورة"
                                        class="form-control @error('course_title') is-invalid is-filled @enderror" />
                                    <label for="modalEditCoursecourse_title">عنوان الدورة</label>
                                </div>
                                @error('course_title')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model='trainer_id' id="modalEditCoursetrainer_id"
                                        class="form-select @error('trainer_id') is-invalid is-filled @enderror">
                                        <option value="">اختر اسم المدرب</option>
                                        @if (class_exists('App\Models\Trainers\Trainers'))
                                            @foreach (App\Models\Trainers\Trainers::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->trainer_name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\Trainer\Trainer'))
                                            @foreach (App\Models\Trainer\Trainer::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->trainer_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('trainer_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model='domain_id' id="modalEditCoursedomain_id"
                                        class="form-select @error('domain_id') is-invalid is-filled @enderror">
                                        <option value="">اختر المجال التدريبي</option>
                                        @if (class_exists('App\Models\TrainingDomains\TrainingDomains'))
                                            @foreach (App\Models\TrainingDomains\TrainingDomains::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\TrainingDomain\TrainingDomain'))
                                            @foreach (App\Models\TrainingDomain\TrainingDomain::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('domain_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model='program_manager_id' id="modalEditCourseprogram_manager_id"
                                        class="form-select @error('program_manager_id') is-invalid is-filled @enderror">
                                        <option value="">اختر مدير البرنامج التدريبي</option>
                                        @if (class_exists('App\Models\Employees\Employees'))
                                            @foreach (App\Models\Employees\Employees::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->employee_name }}
                                                </option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\Employee\Employee'))
                                            @foreach (App\Models\Employee\Employee::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->employee_name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('program_manager_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model='venue_id' id="modalEditCoursevenue_id"
                                        class="form-select @error('venue_id') is-invalid is-filled @enderror">
                                        <option value="">اختر مكان انعقاد الدورة</option>
                                        @if (class_exists('App\Models\Venues\Venues'))
                                            @foreach (App\Models\Venues\Venues::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\Venue\Venue'))
                                            @foreach (App\Models\Venue\Venue::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('venue_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='duration_days' type="text"
                                        id="modalEditCourseduration_days" placeholder="مدة الدورة"
                                        class="form-control @error('duration_days') is-invalid is-filled @enderror" />
                                    <label for="modalEditCourseduration_days">مدة الدورة</label>
                                </div>
                                @error('duration_days')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='course_book_no' type="text"
                                        id="modalEditCoursecourse_book_no" placeholder="رقم كتاب الدورة"
                                        class="form-control @error('course_book_no') is-invalid is-filled @enderror" />
                                    <label for="modalEditCoursecourse_book_no">رقم كتاب الدورة</label>
                                </div>
                                @error('course_book_no')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:ignore wire:model.defer='course_book_date' type="date"
                                        id="modalEditCoursecourse_book_date" placeholder="تاريخ كتاب الدورة"
                                        class="form-control @error('course_book_date') is-invalid is-filled @enderror flatpickr-input flatpickr-date" />
                                    <label for="modalEditCoursecourse_book_date">تاريخ كتاب الدورة</label>
                                </div>
                                @error('course_book_date')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='course_book_image_path' type="file"
                                        accept=".jpeg,.png,.jpg,.pdf" id="modalEditCoursecourse_book_image_path"
                                        placeholder="ملف كتاب الدورة"
                                        class="form-control @error('course_book_image_path') is-invalid is-filled @enderror"
                                        onchange="showFileSelected(this, 'fileSelectedEditcourse_book_image_path')" />
                                    <label for="modalEditCoursecourse_book_image_path">ملف كتاب الدورة</label>
                                </div>
                                <!-- File selection indicator for edit -->
                                <div id="fileSelectedEditcourse_book_image_path" class="mt-2"
                                    style="display: none;">
                                    <div class="alert alert-success py-2 px-3">
                                        <small class="text-success d-flex align-items-center">
                                            <i class="mdi mdi-check-circle me-2" style="font-size: 1.1em;"></i>
                                            <span>تم اختيار الملف: </span>
                                            <span class="fw-bold ms-1" id="fileNameEditcourse_book_image_path"></span>
                                        </small>
                                    </div>
                                </div>
                                @if ($previewFilecourse_book_image_path)
                                    <div class="mt-2">
                                        <small class="text-muted">الملف الحالي:</small>
                                        <a href="{{ Storage::url('courses/' . $previewFilecourse_book_image_path) }}"
                                            target="_blank" class="btn btn-sm btn-outline-info">عرض الملف</a>
                                    </div>
                                @endif
                                @error('course_book_image_path')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='postpone_book_no' type="text"
                                        id="modalEditCoursepostpone_book_no" placeholder="رقم كتاب التاجيل"
                                        class="form-control @error('postpone_book_no') is-invalid is-filled @enderror" />
                                    <label for="modalEditCoursepostpone_book_no">رقم كتاب التاجيل</label>
                                </div>
                                @error('postpone_book_no')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:ignore wire:model.defer='postpone_book_date' type="date"
                                        id="modalEditCoursepostpone_book_date" placeholder="تاريخ كتاب التاجيل"
                                        class="form-control @error('postpone_book_date') is-invalid is-filled @enderror flatpickr-input flatpickr-date" />
                                    <label for="modalEditCoursepostpone_book_date">تاريخ كتاب التاجيل</label>
                                </div>
                                @error('postpone_book_date')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='postpone_book_image_path' type="file"
                                        accept=".jpeg,.png,.jpg,.pdf" id="modalEditCoursepostpone_book_image_path"
                                        placeholder="ملف كتاب التاجيل"
                                        class="form-control @error('postpone_book_image_path') is-invalid is-filled @enderror"
                                        onchange="showFileSelected(this, 'fileSelectedEditpostpone_book_image_path')" />
                                    <label for="modalEditCoursepostpone_book_image_path">ملف كتاب التاجيل</label>
                                </div>
                                <!-- File selection indicator for edit -->
                                <div id="fileSelectedEditpostpone_book_image_path" class="mt-2"
                                    style="display: none;">
                                    <div class="alert alert-success py-2 px-3">
                                        <small class="text-success d-flex align-items-center">
                                            <i class="mdi mdi-check-circle me-2" style="font-size: 1.1em;"></i>
                                            <span>تم اختيار الملف: </span>
                                            <span class="fw-bold ms-1"
                                                id="fileNameEditpostpone_book_image_path"></span>
                                        </small>
                                    </div>
                                </div>
                                @if ($previewFilepostpone_book_image_path)
                                    <div class="mt-2">
                                        <small class="text-muted">الملف الحالي:</small>
                                        <a href="{{ Storage::url('courses/' . $previewFilepostpone_book_image_path) }}"
                                            target="_blank" class="btn btn-sm btn-outline-info">عرض الملف</a>
                                    </div>
                                @endif
                                @error('postpone_book_image_path')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <textarea wire:model='notes' id="modalEditCoursenotes" placeholder="ملاحظات"
                                        class="form-control h-px-100 @error('notes') is-invalid is-filled @enderror"></textarea>
                                    <label for="modalEditCoursenotes">ملاحظات</label>
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
<!--/ Edite Course Modal -->
