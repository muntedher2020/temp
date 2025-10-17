<?php

namespace App\Http\Livewire\Courses;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CoursesExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Courses\Courses as CourseModel;
use App\Models\System\ModuleField;
use App\Models\Trainers\Trainers;
use App\Models\Trainer\Trainer;
use App\Models\TrainingDomains\TrainingDomains;
use App\Models\TrainingDomain\TrainingDomain;
use App\Models\Employees\Employees;
use App\Models\Employee\Employee;
use App\Models\Venues\Venues;
use App\Models\Venue\Venue;

class Course extends Component
{
    use WithPagination;
    use WithFileUploads;
    protected $paginationTheme = 'bootstrap';

    public $Courses = [];
    public $course;
    public $course_title;
    public $trainer_id;
    public $domain_id;
    public $program_manager_id;
    public $venue_id;
    public $duration_days;
    public $course_book_no;
    public $course_book_date;
    public $course_book_image_path;
    public $previewFilecourse_book_image_path;
    public $postpone_book_no;
    public $postpone_book_date;
    public $postpone_book_image_path;
    public $previewFilepostpone_book_image_path;
    public $notes;
    public $search = ['course_title' => '', 'trainer_id' => '', 'domain_id' => '', 'program_manager_id' => '', 'venue_id' => '', 'duration_days' => '', 'course_book_no' => '', 'course_book_date' => '', 'course_book_image_path' => '', 'postpone_book_no' => '', 'postpone_book_date' => '', 'postpone_book_image_path' => '', 'notes' => ''];
    public $selectedRows = [];
    public $selectAll = false;

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['course_title', 'trainer_id', 'domain_id', 'program_manager_id', 'venue_id', 'duration_days', 'course_book_no', 'course_book_date', 'course_book_image_path', 'postpone_book_no', 'postpone_book_date', 'postpone_book_image_path', 'notes'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
    }

    public function render()
    {

        $course_titleSearch = '%' . $this->search['course_title'] . '%';
        $trainer_idSearch = $this->search['trainer_id'];
        $domain_idSearch = $this->search['domain_id'];
        $program_manager_idSearch = $this->search['program_manager_id'];
        $venue_idSearch = $this->search['venue_id'];
        $duration_daysSearch = '%' . $this->search['duration_days'] . '%';
        $course_book_noSearch = '%' . $this->search['course_book_no'] . '%';
        $course_book_dateSearch = '%' . $this->search['course_book_date'] . '%';
        $course_book_image_pathSearch = '%' . $this->search['course_book_image_path'] . '%';
        $postpone_book_noSearch = '%' . $this->search['postpone_book_no'] . '%';
        $postpone_book_dateSearch = '%' . $this->search['postpone_book_date'] . '%';
        $postpone_book_image_pathSearch = '%' . $this->search['postpone_book_image_path'] . '%';
        $notesSearch = '%' . $this->search['notes'] . '%';
        $Courses = CourseModel::query()
            ->when($this->search['course_title'], function ($query) use ($course_titleSearch) {
                $query->where('course_title', 'LIKE', $course_titleSearch);
            })
            ->when($this->search['trainer_id'], function ($query) use ($trainer_idSearch) {
                $query->where('trainer_id', $trainer_idSearch);
            })
            ->when($this->search['domain_id'], function ($query) use ($domain_idSearch) {
                $query->where('domain_id', $domain_idSearch);
            })
            ->when($this->search['program_manager_id'], function ($query) use ($program_manager_idSearch) {
                $query->where('program_manager_id', $program_manager_idSearch);
            })
            ->when($this->search['venue_id'], function ($query) use ($venue_idSearch) {
                $query->where('venue_id', $venue_idSearch);
            })
            ->when($this->search['duration_days'], function ($query) use ($duration_daysSearch) {
                $query->where('duration_days', 'LIKE', $duration_daysSearch);
            })
            ->when($this->search['course_book_no'], function ($query) use ($course_book_noSearch) {
                $query->where('course_book_no', 'LIKE', $course_book_noSearch);
            })
            ->when($this->search['course_book_date'], function ($query) use ($course_book_dateSearch) {
                $query->where('course_book_date', 'LIKE', $course_book_dateSearch);
            })
            ->when($this->search['course_book_image_path'], function ($query) use ($course_book_image_pathSearch) {
                $query->where('course_book_image_path', 'LIKE', $course_book_image_pathSearch);
            })
            ->when($this->search['postpone_book_no'], function ($query) use ($postpone_book_noSearch) {
                $query->where('postpone_book_no', 'LIKE', $postpone_book_noSearch);
            })
            ->when($this->search['postpone_book_date'], function ($query) use ($postpone_book_dateSearch) {
                $query->where('postpone_book_date', 'LIKE', $postpone_book_dateSearch);
            })
            ->when($this->search['postpone_book_image_path'], function ($query) use ($postpone_book_image_pathSearch) {
                $query->where('postpone_book_image_path', 'LIKE', $postpone_book_image_pathSearch);
            })
            ->when($this->search['notes'], function ($query) use ($notesSearch) {
                $query->where('notes', 'LIKE', $notesSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $Courses;
        $this->Courses = collect($Courses->items());

        return view('livewire.courses.course', [
            'Courses' => $Courses,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('Courses', false);
            return $rules ?: [
                'course_title' => 'required|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'trainer_id' => 'required',
            'domain_id' => 'required',
            'program_manager_id' => 'required',
            'venue_id' => 'required',
            'duration_days' => 'required|max:5|regex:/^[0-9]+$/',
            'course_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'course_book_date' => 'required',
            'course_book_image_path' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'postpone_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'course_title' => 'required|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'trainer_id' => 'required',
            'domain_id' => 'required',
            'program_manager_id' => 'required',
            'venue_id' => 'required',
            'duration_days' => 'required|max:5|regex:/^[0-9]+$/',
            'course_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'course_book_date' => 'required',
            'course_book_image_path' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'postpone_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('Courses', true, $this->course->id ?? null);
            return $rules ?: [
                'course_title' => 'required|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'trainer_id' => 'required',
            'domain_id' => 'required',
            'program_manager_id' => 'required',
            'venue_id' => 'required',
            'duration_days' => 'required|max:5|regex:/^[0-9]+$/',
            'course_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'course_book_date' => 'required',
            'course_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'postpone_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'course_title' => 'required|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'trainer_id' => 'required',
            'domain_id' => 'required',
            'program_manager_id' => 'required',
            'venue_id' => 'required',
            'duration_days' => 'required|max:5|regex:/^[0-9]+$/',
            'course_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'course_book_date' => 'required',
            'course_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'postpone_book_image_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('Courses');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'course_title.required' => 'يرجى إدخال عنوان الدورة',
            'course_title.max' => 'عنوان الدورة يجب أن يكون أقل من 255 حرف',
            'course_title.regex' => 'عنوان الدورة يجب أن يحتوي على أحرف عربية فقط',
            'trainer_id.required' => 'يرجى إدخال اسم المدرب',
            'domain_id.required' => 'يرجى إدخال المجال التدريبي',
            'program_manager_id.required' => 'يرجى إدخال مدير البرنامج التدريبي',
            'venue_id.required' => 'يرجى إدخال مكان انعقاد الدورة',
            'duration_days.required' => 'يرجى إدخال مدة الدورة',
            'duration_days.max' => 'مدة الدورة يجب أن يكون أقل من 5 حرف',
            'duration_days.regex' => 'مدة الدورة يجب أن يحتوي على أرقام فقط',
            'course_book_no.required' => 'يرجى إدخال رقم كتاب الدورة',
            'course_book_no.max' => 'رقم كتاب الدورة يجب أن يكون أقل من 50 حرف',
            'course_book_no.regex' => 'رقم كتاب الدورة يجب أن يحتوي على أرقام فقط',
            'course_book_date.required' => 'يرجى إدخال تاريخ كتاب الدورة',
            'course_book_image_path.required' => 'يرجى اختيار ملف كتاب الدورة',
            'course_book_image_path.file' => 'ملف كتاب الدورة يجب أن يكون ملف',
            'course_book_image_path.mimes' => 'ملف كتاب الدورة يجب أن يكون من نوع صورة أو PDF',
            'course_book_image_path.max' => 'حجم ملف كتاب الدورة يجب ألا يزيد عن 10 ميجا',
            'postpone_book_image_path.file' => 'ملف كتاب التاجيل يجب أن يكون ملف',
            'postpone_book_image_path.mimes' => 'ملف كتاب التاجيل يجب أن يكون من نوع صورة أو PDF',
            'postpone_book_image_path.max' => 'حجم ملف كتاب التاجيل يجب ألا يزيد عن 10 ميجا'
        ];
    }

    public function AddCourseModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('CourseModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            if ($this->course_book_image_path) {
            $this->course_book_image_path->store('public/courses');
            $fileData['course_book_image_path'] = $this->course_book_image_path->hashName();
        }
        if ($this->postpone_book_image_path) {
            $this->postpone_book_image_path->store('public/courses');
            $fileData['postpone_book_image_path'] = $this->postpone_book_image_path->hashName();
        }

            CourseModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'course_title' => $this->course_title,
            'trainer_id' => $this->trainer_id,
            'domain_id' => $this->domain_id,
            'program_manager_id' => $this->program_manager_id,
            'venue_id' => $this->venue_id,
            'duration_days' => $this->duration_days,
            'course_book_no' => $this->course_book_no,
            'course_book_date' => $this->course_book_date,
            'course_book_image_path' => $this->course_book_image_path->hashName(),
            'postpone_book_no' => $this->postpone_book_no,
            'postpone_book_date' => $this->postpone_book_date,
            'postpone_book_image_path' => $this->postpone_book_image_path ? $this->postpone_book_image_path->hashName() : null,
            'notes' => $this->notes
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الدورات التدريبية',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة الدورات التدريبية جديد",
            ]);
            // =================================
            $this->reset();
            $this->dispatchBrowserEvent('success', [
                'message' => 'تم الاضافه بنجاح',
                'title' => 'اضافه'
            ]);
        } catch (ValidationException $e) {
            // Re-throw validation exceptions to show field-specific errors
            throw $e;
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء الإضافة: ' . $e->getMessage(),
                'title' => 'خطأ'
            ]);
        }
    }

    public function GetCourse($courseId)
    {
        $this->resetValidation();

        $this->course  = CourseModel::find($courseId);
        $this->course_title = $this->course->course_title;
        $this->trainer_id = $this->course->trainer_id;
        $this->domain_id = $this->course->domain_id;
        $this->program_manager_id = $this->course->program_manager_id;
        $this->venue_id = $this->course->venue_id;
        $this->duration_days = $this->course->duration_days;
        $this->course_book_no = $this->course->course_book_no;
        $this->course_book_date = $this->course->course_book_date;
        $this->previewFilecourse_book_image_path = $this->course->course_book_image_path; // For preview
        $this->course_book_image_path = null; // Reset file input for new upload
        $this->postpone_book_no = $this->course->postpone_book_no;
        $this->postpone_book_date = $this->course->postpone_book_date;
        $this->previewFilepostpone_book_image_path = $this->course->postpone_book_image_path; // For preview
        $this->postpone_book_image_path = null; // Reset file input for new upload
        $this->notes = $this->course->notes;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('courseDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $Course = CourseModel::find($this->course->id ?? null);
            if (!$Course) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            $updateData = [
                'user_id' => Auth::user()->id,
            'course_title' => $this->course_title,
            'trainer_id' => $this->trainer_id,
            'domain_id' => $this->domain_id,
            'program_manager_id' => $this->program_manager_id,
            'venue_id' => $this->venue_id,
            'duration_days' => $this->duration_days,
            'course_book_no' => $this->course_book_no,
            'course_book_date' => $this->course_book_date,
            // 'course_book_image_path' will be handled separately if updated,
            'postpone_book_no' => $this->postpone_book_no,
            'postpone_book_date' => $this->postpone_book_date,
            // 'postpone_book_image_path' will be handled separately if updated,
            'notes' => $this->notes
            ];

            // Handle file upload if new file is provided
        if ($this->course_book_image_path) {
            $this->course_book_image_path->store('public/courses');
            $updateData['course_book_image_path'] = $this->course_book_image_path->hashName();
        }
        // Handle file upload if new file is provided
        if ($this->postpone_book_image_path) {
            $this->postpone_book_image_path->store('public/courses');
            $updateData['postpone_book_image_path'] = $this->postpone_book_image_path->hashName();
        }

            $Course->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الدورات التدريبية',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل الدورات التدريبية",
            ]);
            // =================================
            $this->reset();
            $this->dispatchBrowserEvent('success', [
                'message' => 'تم التعديل بنجاح',
                'title' => 'تعديل'
            ]);
        } catch (ValidationException $e) {
            // Re-throw validation exceptions to show field-specific errors
            throw $e;
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء التعديل: ' . $e->getMessage(),
                'title' => 'خطأ'
            ]);
        }
    }

    public function destroy()
    {
        $Course = CourseModel::find($this->course->id ?? null);

        if ($Course) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الدورات التدريبية',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف الدورات التدريبية",
            ]);
            // =================================
            $Course->delete();
            $this->reset();
            $this->dispatchBrowserEvent('success', [
                'message' => 'تم حذف البيانات بنجاح',
                'title' => 'الحذف'
            ]);
        }
    }

    // Export to Excel
    public function exportExcel()
    {
        $fileName = 'الدورات التدريبية_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new CoursesExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - CourseTcpdfExportController for TCPDF export
    // - CoursePrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = CourseModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = CourseModel::count();
        $this->selectAll = count($this->selectedRows) === $totalCount;
    }

    public function exportSelected()
    {
        if (empty($this->selectedRows)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ',
                'message' => 'الرجاء تحديد صف واحد على الأقل'
            ]);
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        // Set headers

        $headers = ['ID', 'عنوان الدورة', 'اسم المدرب', 'المجال التدريبي', 'مدير البرنامج التدريبي', 'مكان انعقاد الدورة', 'مدة الدورة', 'رقم كتاب الدورة', 'تاريخ كتاب الدورة', 'ملف كتاب الدورة', 'رقم كتاب التاجيل', 'تاريخ كتاب التاجيل', 'ملف كتاب التاجيل', 'ملاحظات'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
                'name' => 'Arial'
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A6CF7']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        $items = CourseModel::whereIn('id', $this->selectedRows)->get();
        foreach ($items as $item) {

            $data = [$item->id, $item->course_title, $item->trainer_id, $item->domain_id, $item->program_manager_id, $item->venue_id, $item->duration_days, $item->course_book_no, $item->course_book_date ? \Carbon\Carbon::parse($item->course_book_date)->format('Y/m/d') : '', $item->course_book_image_path, $item->postpone_book_no, $item->postpone_book_date ? \Carbon\Carbon::parse($item->postpone_book_date)->format('Y/m/d') : '', $item->postpone_book_image_path, $item->notes];
            $sheet->fromArray([$data], NULL, 'A' . $row);
            $row++;
        }

        // Data styling
        $dataRange = 'A2:' . chr(64 + count($headers)) . ($row - 1);
        $dataStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        $sheet->getStyle($dataRange)->applyFromArray($dataStyle);

        // Auto-size columns
        foreach (range('A', chr(64 + count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fileName = 'courses_' . date('Y-m-d_H-i-s') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $path = storage_path('app/public/exports');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $fullPath = $path . '/' . $fileName;
        $writer->save($fullPath);

        return response()->download($fullPath)->deleteFileAfterSend();
    }
}