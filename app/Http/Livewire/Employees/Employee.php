<?php

namespace App\Http\Livewire\Employees;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Employees\Employees as EmployeeModel;
use App\Models\System\ModuleField;
use App\Models\EducationalLevels\EducationalLevels;
use App\Models\EducationalLevel\EducationalLevel;
use App\Models\Departments\Departments;
use App\Models\Department\Department;
use App\Models\JobTitles\JobTitles;
use App\Models\JobTitle\JobTitle;
use App\Models\JobGrades\JobGrades;
use App\Models\JobGrade\JobGrade;

class Employee extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Employees = [];
    public $employee;
    public $employee_name;
    public $gender;
    public $ed_level_id;
    public $department_id;
    public $job_title_id;
    public $job_grade_id;
    public $notes;
    public $search = ['employee_name' => '', 'gender' => '', 'ed_level_id' => '', 'department_id' => '', 'job_title_id' => '', 'job_grade_id' => '', 'notes' => ''];
    public $selectedRows = [];
    public $selectAll = false;

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['employee_name', 'gender', 'ed_level_id', 'department_id', 'job_title_id', 'job_grade_id', 'notes'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
    }

    public function render()
    {

        $employee_nameSearch = '%' . $this->search['employee_name'] . '%';
        $genderSearch = $this->search['gender'];
        $ed_level_idSearch = $this->search['ed_level_id'];
        $department_idSearch = $this->search['department_id'];
        $job_title_idSearch = $this->search['job_title_id'];
        $job_grade_idSearch = $this->search['job_grade_id'];
        $notesSearch = '%' . $this->search['notes'] . '%';
        $Employees = EmployeeModel::query()
            ->when($this->search['employee_name'], function ($query) use ($employee_nameSearch) {
                $query->where('employee_name', 'LIKE', $employee_nameSearch);
            })
            ->when($this->search['gender'], function ($query) use ($genderSearch) {
                $query->where('gender', $genderSearch);
            })
            ->when($this->search['ed_level_id'], function ($query) use ($ed_level_idSearch) {
                $query->where('ed_level_id', $ed_level_idSearch);
            })
            ->when($this->search['department_id'], function ($query) use ($department_idSearch) {
                $query->where('department_id', $department_idSearch);
            })
            ->when($this->search['job_title_id'], function ($query) use ($job_title_idSearch) {
                $query->where('job_title_id', $job_title_idSearch);
            })
            ->when($this->search['job_grade_id'], function ($query) use ($job_grade_idSearch) {
                $query->where('job_grade_id', $job_grade_idSearch);
            })
            ->when($this->search['notes'], function ($query) use ($notesSearch) {
                $query->where('notes', 'LIKE', $notesSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $Employees;
        $this->Employees = collect($Employees->items());

        return view('livewire.employees.employee', [
            'Employees' => $Employees,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('Employees', false);
            return $rules ?: [
                'employee_name' => 'required|unique:employees,employee_name|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'gender' => 'required',
            'ed_level_id' => 'required',
            'department_id' => 'required',
            'job_title_id' => 'required',
            'job_grade_id' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'employee_name' => 'required|unique:employees,employee_name|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'gender' => 'required',
            'ed_level_id' => 'required',
            'department_id' => 'required',
            'job_title_id' => 'required',
            'job_grade_id' => 'required'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('Employees', true, $this->employee->id ?? null);
            return $rules ?: [
                'employee_name' => 'required|unique:employees,employee_name,' . ($this->employee->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'gender' => 'required',
            'ed_level_id' => 'required',
            'department_id' => 'required',
            'job_title_id' => 'required',
            'job_grade_id' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'employee_name' => 'required|unique:employees,employee_name,' . ($this->employee->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'gender' => 'required',
            'ed_level_id' => 'required',
            'department_id' => 'required',
            'job_title_id' => 'required',
            'job_grade_id' => 'required'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('Employees');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'employee_name.required' => 'يرجى إدخال اسم الموظف',
            'employee_name.unique' => 'اسم الموظف موجود بالفعل',
            'employee_name.max' => 'اسم الموظف يجب أن يكون أقل من 255 حرف',
            'employee_name.regex' => 'اسم الموظف يجب أن يحتوي على أحرف عربية فقط',
            'gender.required' => 'يرجى إدخال الجنس',
            'ed_level_id.required' => 'يرجى إدخال التحصيل العلمي',
            'department_id.required' => 'يرجى إدخال القسم',
            'job_title_id.required' => 'يرجى إدخال العنوان الوظيفي',
            'job_grade_id.required' => 'يرجى إدخال الدرجة الوظيفية'
        ];
    }

    public function AddEmployeeModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('EmployeeModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            EmployeeModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'employee_name' => $this->employee_name,
            'gender' => $this->gender,
            'ed_level_id' => $this->ed_level_id,
            'department_id' => $this->department_id,
            'job_title_id' => $this->job_title_id,
            'job_grade_id' => $this->job_grade_id,
            'notes' => $this->notes
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الموظفين',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة الموظفين جديد",
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

    public function GetEmployee($employeeId)
    {
        $this->resetValidation();

        $this->employee  = EmployeeModel::find($employeeId);
        $this->employee_name = $this->employee->employee_name;
        $this->gender = $this->employee->gender;
        $this->ed_level_id = $this->employee->ed_level_id;
        $this->department_id = $this->employee->department_id;
        $this->job_title_id = $this->employee->job_title_id;
        $this->job_grade_id = $this->employee->job_grade_id;
        $this->notes = $this->employee->notes;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('employeeDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $Employee = EmployeeModel::find($this->employee->id ?? null);
            if (!$Employee) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            $updateData = [
                'user_id' => Auth::user()->id,
            'employee_name' => $this->employee_name,
            'gender' => $this->gender,
            'ed_level_id' => $this->ed_level_id,
            'department_id' => $this->department_id,
            'job_title_id' => $this->job_title_id,
            'job_grade_id' => $this->job_grade_id,
            'notes' => $this->notes
            ];

            

            $Employee->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الموظفين',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل الموظفين",
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
        $Employee = EmployeeModel::find($this->employee->id ?? null);

        if ($Employee) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'الموظفين',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف الموظفين",
            ]);
            // =================================
            $Employee->delete();
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
        $fileName = 'الموظفين_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new EmployeesExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - EmployeeTcpdfExportController for TCPDF export
    // - EmployeePrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = EmployeeModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = EmployeeModel::count();
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

        $headers = ['ID', 'اسم الموظف', 'الجنس', 'التحصيل العلمي', 'القسم', 'العنوان الوظيفي', 'الدرجة الوظيفية', 'ملاحظات'];
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
        $items = EmployeeModel::whereIn('id', $this->selectedRows)->get();
        foreach ($items as $item) {

            $data = [$item->id, $item->employee_name, $item->gender, $item->ed_level_id, $item->department_id, $item->job_title_id, $item->job_grade_id, $item->notes];
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

        $fileName = 'employees_' . date('Y-m-d_H-i-s') . '.xlsx';
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