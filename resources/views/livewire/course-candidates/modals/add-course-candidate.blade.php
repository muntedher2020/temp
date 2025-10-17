<!-- Add CourseCandidate Modal -->
<div wire:ignore.self class="modal fade" id="addcoursecandidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-primary">اضافة</span> المتدربين والمرشحين جديد
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-cog me-1"></i>
                            قم بإدخال تفاصيل المتدربين والمرشحين في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="store, GetCourseCandidate">
                    <form id="addcoursecandidateModalForm" autocomplete="off">
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model.defer='employee_id' id="modalCourseCandidateemployee_id"
                                        class="form-select @error('employee_id') is-invalid is-filled @enderror">
                                        <option value="">اختر اسم الموظف</option>
                                        @if (class_exists('App\Models\Employees\Employees'))
                                            @foreach (App\Models\Employees\Employees::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->employee_name }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\Employee\Employee'))
                                            @foreach (App\Models\Employee\Employee::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->employee_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('employee_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model.defer='course_id' id="modalCourseCandidatecourse_id"
                                        class="form-select @error('course_id') is-invalid is-filled @enderror">
                                        <option value="">اختر عنوان الدورة</option>
                                        @if (class_exists('App\Models\Courses\Courses'))
                                            @foreach (App\Models\Courses\Courses::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->course_title }}</option>
                                            @endforeach
                                        @elseif(class_exists('App\Models\Course\Course'))
                                            @foreach (App\Models\Course\Course::all() as $item)
                                                <option value="{{ $item->id }}">{{ $item->course_title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('course_id')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='nomination_book_no' type="text"
                                        id="modalCourseCandidatenomination_book_no" placeholder="رقم كتاب الترشيح"
                                        class="form-control @error('nomination_book_no') is-invalid is-filled @enderror" />
                                    <label for="modalCourseCandidatenomination_book_no">رقم كتاب الترشيح</label>
                                </div>
                                @error('nomination_book_no')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:ignore wire:model.defer='nomination_book_date' type="date"
                                        id="modalCourseCandidatenomination_book_date" placeholder="تاريخ كتاب الترشيح"
                                        class="form-control @error('nomination_book_date') is-invalid is-filled @enderror flatpickr-input flatpickr-date" />
                                    <label for="modalCourseCandidatenomination_book_date">تاريخ كتاب الترشيح</label>
                                </div>
                                @error('nomination_book_date')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model.defer='pre_training_level'
                                        id="modalCourseCandidatepre_training_level"
                                        class="form-select @error('pre_training_level') is-invalid is-filled @enderror">
                                        <option value="">اختر المستوى قبل التدريب</option>
                                        <option value="ضعيف">ضعيف</option>
                                        <option value="مقبول">مقبول</option>
                                        <option value="متوسط">متوسط</option>
                                        <option value="جيد">جيد</option>
                                        <option value="جيد جدا">جيد جدا</option>
                                        <option value="ممتاز">ممتاز</option>
                                    </select>
                                </div>
                                @error('pre_training_level')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-check form-switch">
                                    <input wire:model.defer='passed' type="checkbox" id="modalCourseCandidatepassed"
                                        value="1" class="form-check-input @error('passed') is-invalid @enderror" />
                                    <label class="form-check-label" for="modalCourseCandidatepassed">هل اجتاز
                                        الدورة</label>
                                </div>
                                @error('passed')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline" wire:ignore>
                                    <select wire:model.defer='post_training_level'
                                        id="modalCourseCandidatepost_training_level"
                                        class="form-select @error('post_training_level') is-invalid is-filled @enderror">
                                        <option value="">اختر المستوى بعد التدرب</option>
                                        <option value="ضعيف">ضعيف</option>
                                        <option value="مقبول">مقبول</option>
                                        <option value="متوسط">متوسط</option>
                                        <option value="جيد">جيد</option>
                                        <option value="جيد جدا">جيد جدا</option>
                                        <option value="ممتاز">ممتاز</option>
                                    </select>
                                </div>
                                @error('post_training_level')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='attendance_days' type="text"
                                        id="modalCourseCandidateattendance_days" placeholder="عدد ايام الحضور"
                                        class="form-control @error('attendance_days') is-invalid is-filled @enderror" />
                                    <label for="modalCourseCandidateattendance_days">عدد ايام الحضور</label>
                                </div>
                                @error('attendance_days')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <input wire:model.defer='absence_days' type="text"
                                        id="modalCourseCandidateabsence_days" placeholder="عدد ايام الغياب"
                                        class="form-control @error('absence_days') is-invalid is-filled @enderror" />
                                    <label for="modalCourseCandidateabsence_days">عدد ايام الغياب</label>
                                </div>
                                @error('absence_days')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <div class="form-floating form-floating-outline">
                                    <textarea wire:model.defer='notes' id="modalCourseCandidatenotes" placeholder="ملاحظات"
                                        class="form-control h-px-100 @error('notes') is-invalid is-filled @enderror"></textarea>
                                    <label for="modalCourseCandidatenotes">ملاحظات</label>
                                </div>
                                @error('notes')
                                    <small class='text-danger inputerror'> {{ $message }} </small>
                                @enderror
                            </div>
                        </div>
                        <hr class="my-0">
                        <div class="text-center col-12 demo-vertical-spacing mb-n4">
                            <button wire:click='store' wire:loading.attr="disabled" type="button"
                                class="btn btn-primary me-sm-3 me-1">اضافة</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                aria-label="Close">تجاهل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Add CourseCandidate Modal -->
