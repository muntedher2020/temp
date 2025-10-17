<!-- Edite Trainer Modal -->
<div wire:ignore.self class="modal fade" id="edittrainerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">
                            <span class="text-warning">تعديل</span> بيانات المدربين
                        </h3>
                        <p class="text-muted">
                            <i class="mdi mdi-cog me-1"></i>
                            قم بتعديل تفاصيل المدربين في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="update, GetTrainer">
                    <form id="editTrainerModalForm" autocomplete="off">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input wire:model.defer='trainer_name' type="text"
                                                id="modalEditTrainertrainer_name" placeholder="اسم المدرب"
                                                class="form-control @error('trainer_name') is-invalid is-filled @enderror" />
                                            <label for="modalEditTrainertrainer_name">اسم المدرب</label>
                                        </div>
                                        @error('trainer_name')
                                            <small class='text-danger inputerror'> {{ $message }} </small>
                                        @enderror
                                    </div>
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='institution_id'
                                            id="modalEditTrainerinstitution_id"
                                            class="form-select @error('institution_id') is-invalid is-filled @enderror">
                                            <option value="">اختر مؤسسة المدرب</option>
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
                                    </div>
                                    @error('institution_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                    <div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model='ed_level_id'
                                            id="modalEditTrainered_level_id"
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
                                        <select wire:model='domain_id'
                                            id="modalEditTrainerdomain_id"
                                            class="form-select @error('domain_id') is-invalid is-filled @enderror">
                                            <option value="">اختر المجال التدريبي</option>
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
                                    </div>
                                    @error('domain_id')
                                        <small class='text-danger inputerror'> {{ $message }} </small>
                                    @enderror
                                </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input wire:model.defer='phone' type="text"
                                                id="modalEditTrainerphone" placeholder="رقم الهاتف"
                                                class="form-control @error('phone') is-invalid is-filled @enderror" />
                                            <label for="modalEditTrainerphone">رقم الهاتف</label>
                                        </div>
                                        @error('phone')
                                            <small class='text-danger inputerror'> {{ $message }} </small>
                                        @enderror
                                    </div>
                            <div class="mb-3 col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <input wire:model.defer='email' type="email"
                                                id="modalEditTraineremail" placeholder="name@example.com"
                                                class="form-control @error('email') is-invalid is-filled @enderror" />
                                            <label for="modalEditTraineremail">البريد الالكتروني</label>
                                        </div>
                                        @error('email')
                                            <small class='text-danger inputerror'> {{ $message }} </small>
                                        @enderror
                                    </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                        <div class="form-floating form-floating-outline">
                                            <textarea wire:model='notes'
                                                id="modalEditTrainernotes" placeholder="ملاحظات"
                                                class="form-control h-px-100 @error('notes') is-invalid is-filled @enderror"></textarea>
                                            <label for="modalEditTrainernotes">ملاحظات</label>
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
<!--/ Edite Trainer Modal -->
