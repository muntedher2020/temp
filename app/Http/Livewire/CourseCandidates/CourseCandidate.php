<?php

namespace App\Http\Livewire\CourseCandidates;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CourseCandidatesExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\CourseCandidates\CourseCandidates as CourseCandidateModel;
use App\Models\System\ModuleField;
use App\Models\Employees\Employees;
use App\Models\Employee\Employee;
use App\Models\Courses\Courses;
use App\Models\Course\Course;

class CourseCandidate extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $CourseCandidates = [];
    public $coursecandidate;
    public $employee_id;
    public $course_id;
    public $nomination_book_no;
    public $nomination_book_date;
    public $pre_training_level;
    public $passed = false; // Initialize as false for checkbox
    public $post_training_level;
    public $attendance_days;
    public $absence_days;
    public $notes;
    public $search = ['employee_id' => '', 'course_id' => '', 'nomination_book_no' => '', 'nomination_book_date' => '', 'pre_training_level' => '', 'passed' => '', 'post_training_level' => '', 'attendance_days' => '', 'absence_days' => '', 'notes' => ''];
    public $selectedRows = [];
    public $selectAll = false;

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['employee_id', 'course_id', 'nomination_book_no', 'nomination_book_date', 'pre_training_level', 'passed', 'post_training_level', 'attendance_days', 'absence_days', 'notes'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
    }

    public function render()
    {

        $employee_idSearch = $this->search['employee_id'];
        $course_idSearch = $this->search['course_id'];
        $nomination_book_noSearch = '%' . $this->search['nomination_book_no'] . '%';
        $nomination_book_dateSearch = '%' . $this->search['nomination_book_date'] . '%';
        $pre_training_levelSearch = $this->search['pre_training_level'];
        $passedSearch = $this->search['passed'];
        $post_training_levelSearch = $this->search['post_training_level'];
        $attendance_daysSearch = '%' . $this->search['attendance_days'] . '%';
        $absence_daysSearch = '%' . $this->search['absence_days'] . '%';
        $notesSearch = '%' . $this->search['notes'] . '%';
        $CourseCandidates = CourseCandidateModel::query()
            ->when($this->search['employee_id'], function ($query) use ($employee_idSearch) {
                $query->where('employee_id', $employee_idSearch);
            })
            ->when($this->search['course_id'], function ($query) use ($course_idSearch) {
                $query->where('course_id', $course_idSearch);
            })
            ->when($this->search['nomination_book_no'], function ($query) use ($nomination_book_noSearch) {
                $query->where('nomination_book_no', 'LIKE', $nomination_book_noSearch);
            })
            ->when($this->search['nomination_book_date'], function ($query) use ($nomination_book_dateSearch) {
                $query->where('nomination_book_date', 'LIKE', $nomination_book_dateSearch);
            })
            ->when($this->search['pre_training_level'], function ($query) use ($pre_training_levelSearch) {
                $query->where('pre_training_level', $pre_training_levelSearch);
            })
            ->when($this->search['passed'] !== '' && $this->search['passed'] !== null, function ($query) use ($passedSearch) {
                $query->where('passed', (bool)$passedSearch);
            })
            ->when($this->search['post_training_level'], function ($query) use ($post_training_levelSearch) {
                $query->where('post_training_level', $post_training_levelSearch);
            })
            ->when($this->search['attendance_days'], function ($query) use ($attendance_daysSearch) {
                $query->where('attendance_days', 'LIKE', $attendance_daysSearch);
            })
            ->when($this->search['absence_days'], function ($query) use ($absence_daysSearch) {
                $query->where('absence_days', 'LIKE', $absence_daysSearch);
            })
            ->when($this->search['notes'], function ($query) use ($notesSearch) {
                $query->where('notes', 'LIKE', $notesSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $CourseCandidates;
        $this->CourseCandidates = collect($CourseCandidates->items());

        return view('livewire.course-candidates.course-candidate', [
            'CourseCandidates' => $CourseCandidates,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('CourseCandidates', false);
            return $rules ?: [
                'employee_id' => 'required',
            'course_id' => 'required',
            'nomination_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'nomination_book_date' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'employee_id' => 'required',
            'course_id' => 'required',
            'nomination_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'nomination_book_date' => 'required'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('CourseCandidates', true, $this->coursecandidate->id ?? null);
            return $rules ?: [
                'employee_id' => 'required',
            'course_id' => 'required',
            'nomination_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'nomination_book_date' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'employee_id' => 'required',
            'course_id' => 'required',
            'nomination_book_no' => 'required|max:50|regex:/^[0-9]+$/',
            'nomination_book_date' => 'required'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('CourseCandidates');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'employee_id.required' => 'يرجى إدخال اسم الموظف',
            'course_id.required' => 'يرجى إدخال عنوان الدورة',
            'nomination_book_no.required' => 'يرجى إدخال رقم كتاب الترشيح',
            'nomination_book_no.max' => 'رقم كتاب الترشيح يجب أن يكون أقل من 50 حرف',
            'nomination_book_no.regex' => 'رقم كتاب الترشيح يجب أن يحتوي على أرقام فقط',
            'nomination_book_date.required' => 'يرجى إدخال تاريخ كتاب الترشيح'
        ];
    }

    public function AddCourseCandidateModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('CourseCandidateModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            CourseCandidateModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'employee_id' => $this->employee_id,
            'course_id' => $this->course_id,
            'nomination_book_no' => $this->nomination_book_no,
            'nomination_book_date' => $this->nomination_book_date,
            'pre_training_level' => $this->pre_training_level,
            'passed' => (bool)$this->passed,
            'post_training_level' => $this->post_training_level,
            'attendance_days' => $this->attendance_days,
            'absence_days' => $this->absence_days,
            'notes' => $this->notes
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المتدربين والمرشحين',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة المتدربين والمرشحين جديد",
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

    public function GetCourseCandidate($coursecandidateId)
    {
        $this->resetValidation();

        $this->coursecandidate  = CourseCandidateModel::find($coursecandidateId);
        $this->employee_id = $this->coursecandidate->employee_id;
        $this->course_id = $this->coursecandidate->course_id;
        $this->nomination_book_no = $this->coursecandidate->nomination_book_no;
        $this->nomination_book_date = $this->coursecandidate->nomination_book_date;
        $this->pre_training_level = $this->coursecandidate->pre_training_level;
        $this->passed = (bool)$this->coursecandidate->passed; // Convert to boolean for checkbox
        $this->post_training_level = $this->coursecandidate->post_training_level;
        $this->attendance_days = $this->coursecandidate->attendance_days;
        $this->absence_days = $this->coursecandidate->absence_days;
        $this->notes = $this->coursecandidate->notes;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('coursecandidateDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $CourseCandidate = CourseCandidateModel::find($this->coursecandidate->id ?? null);
            if (!$CourseCandidate) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            $updateData = [
                'user_id' => Auth::user()->id,
            'employee_id' => $this->employee_id,
            'course_id' => $this->course_id,
            'nomination_book_no' => $this->nomination_book_no,
            'nomination_book_date' => $this->nomination_book_date,
            'pre_training_level' => $this->pre_training_level,
            'passed' => (bool)$this->passed,
            'post_training_level' => $this->post_training_level,
            'attendance_days' => $this->attendance_days,
            'absence_days' => $this->absence_days,
            'notes' => $this->notes
            ];

            

            $CourseCandidate->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المتدربين والمرشحين',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل المتدربين والمرشحين",
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
        $CourseCandidate = CourseCandidateModel::find($this->coursecandidate->id ?? null);

        if ($CourseCandidate) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المتدربين والمرشحين',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف المتدربين والمرشحين",
            ]);
            // =================================
            $CourseCandidate->delete();
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
        $fileName = 'المتدربين والمرشحين_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new CourseCandidatesExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - CourseCandidateTcpdfExportController for TCPDF export
    // - CourseCandidatePrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = CourseCandidateModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = CourseCandidateModel::count();
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

        $headers = ['ID', 'اسم الموظف', 'عنوان الدورة', 'رقم كتاب الترشيح', 'تاريخ كتاب الترشيح', 'المستوى قبل التدريب', 'هل اجتاز الدورة', 'المستوى بعد التدرب', 'عدد ايام الحضور', 'عدد ايام الغياب', 'ملاحظات'];
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
        $items = CourseCandidateModel::whereIn('id', $this->selectedRows)->get();
        foreach ($items as $item) {

            $data = [$item->id, $item->employee_id, $item->course_id, $item->nomination_book_no, $item->nomination_book_date ? \Carbon\Carbon::parse($item->nomination_book_date)->format('Y/m/d') : '', $item->pre_training_level, $item->passed ? 'نعم' : 'لا', $item->post_training_level, $item->attendance_days, $item->absence_days, $item->notes];
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

        $fileName = 'coursecandidates_' . date('Y-m-d_H-i-s') . '.xlsx';
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