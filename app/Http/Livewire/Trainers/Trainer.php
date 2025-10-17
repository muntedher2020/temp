<?php

namespace App\Http\Livewire\Trainers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrainersExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Trainers\Trainers as TrainerModel;
use App\Models\System\ModuleField;
use App\Models\TrainingInstitutions\TrainingInstitutions;
use App\Models\TrainingInstitution\TrainingInstitution;
use App\Models\EducationalLevels\EducationalLevels;
use App\Models\EducationalLevel\EducationalLevel;
use App\Models\TrainingDomains\TrainingDomains;
use App\Models\TrainingDomain\TrainingDomain;

class Trainer extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Trainers = [];
    public $trainer;
    public $trainer_name;
    public $institution_id;
    public $ed_level_id;
    public $domain_id;
    public $phone;
    public $email;
    public $notes;
    public $search = ['trainer_name' => '', 'institution_id' => '', 'ed_level_id' => '', 'domain_id' => '', 'phone' => '', 'email' => '', 'notes' => ''];
    public $selectedRows = [];
    public $selectAll = false;

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['trainer_name', 'institution_id', 'ed_level_id', 'domain_id', 'phone', 'email', 'notes'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
    }

    public function render()
    {

        $trainer_nameSearch = '%' . $this->search['trainer_name'] . '%';
        $institution_idSearch = $this->search['institution_id'];
        $ed_level_idSearch = $this->search['ed_level_id'];
        $domain_idSearch = $this->search['domain_id'];
        $phoneSearch = '%' . $this->search['phone'] . '%';
        $emailSearch = '%' . $this->search['email'] . '%';
        $notesSearch = '%' . $this->search['notes'] . '%';
        $Trainers = TrainerModel::query()
            ->when($this->search['trainer_name'], function ($query) use ($trainer_nameSearch) {
                $query->where('trainer_name', 'LIKE', $trainer_nameSearch);
            })
            ->when($this->search['institution_id'], function ($query) use ($institution_idSearch) {
                $query->where('institution_id', $institution_idSearch);
            })
            ->when($this->search['ed_level_id'], function ($query) use ($ed_level_idSearch) {
                $query->where('ed_level_id', $ed_level_idSearch);
            })
            ->when($this->search['domain_id'], function ($query) use ($domain_idSearch) {
                $query->where('domain_id', $domain_idSearch);
            })
            ->when($this->search['phone'], function ($query) use ($phoneSearch) {
                $query->where('phone', 'LIKE', $phoneSearch);
            })
            ->when($this->search['email'], function ($query) use ($emailSearch) {
                $query->where('email', 'LIKE', $emailSearch);
            })
            ->when($this->search['notes'], function ($query) use ($notesSearch) {
                $query->where('notes', 'LIKE', $notesSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $Trainers;
        $this->Trainers = collect($Trainers->items());

        return view('livewire.trainers.trainer', [
            'Trainers' => $Trainers,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('Trainers', false);
            return $rules ?: [
                'trainer_name' => 'required|unique:trainers,trainer_name|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'institution_id' => 'required',
            'ed_level_id' => 'required',
            'domain_id' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'trainer_name' => 'required|unique:trainers,trainer_name|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'institution_id' => 'required',
            'ed_level_id' => 'required',
            'domain_id' => 'required'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('Trainers', true, $this->trainer->id ?? null);
            return $rules ?: [
                'trainer_name' => 'required|unique:trainers,trainer_name,' . ($this->trainer->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'institution_id' => 'required',
            'ed_level_id' => 'required',
            'domain_id' => 'required'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'trainer_name' => 'required|unique:trainers,trainer_name,' . ($this->trainer->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u',
            'institution_id' => 'required',
            'ed_level_id' => 'required',
            'domain_id' => 'required'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('Trainers');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'trainer_name.required' => 'يرجى إدخال اسم المدرب',
            'trainer_name.unique' => 'اسم المدرب موجود بالفعل',
            'trainer_name.max' => 'اسم المدرب يجب أن يكون أقل من 255 حرف',
            'trainer_name.regex' => 'اسم المدرب يجب أن يحتوي على أحرف عربية فقط',
            'institution_id.required' => 'يرجى إدخال مؤسسة المدرب',
            'ed_level_id.required' => 'يرجى إدخال التحصيل العلمي',
            'domain_id.required' => 'يرجى إدخال المجال التدريبي'
        ];
    }

    public function AddTrainerModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('TrainerModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            TrainerModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'trainer_name' => $this->trainer_name,
            'institution_id' => $this->institution_id,
            'ed_level_id' => $this->ed_level_id,
            'domain_id' => $this->domain_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'notes' => $this->notes
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المدربين',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة المدربين جديد",
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

    public function GetTrainer($trainerId)
    {
        $this->resetValidation();

        $this->trainer  = TrainerModel::find($trainerId);
        $this->trainer_name = $this->trainer->trainer_name;
        $this->institution_id = $this->trainer->institution_id;
        $this->ed_level_id = $this->trainer->ed_level_id;
        $this->domain_id = $this->trainer->domain_id;
        $this->phone = $this->trainer->phone;
        $this->email = $this->trainer->email;
        $this->notes = $this->trainer->notes;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('trainerDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $Trainer = TrainerModel::find($this->trainer->id ?? null);
            if (!$Trainer) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            $updateData = [
                'user_id' => Auth::user()->id,
            'trainer_name' => $this->trainer_name,
            'institution_id' => $this->institution_id,
            'ed_level_id' => $this->ed_level_id,
            'domain_id' => $this->domain_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'notes' => $this->notes
            ];

            

            $Trainer->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المدربين',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل المدربين",
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
        $Trainer = TrainerModel::find($this->trainer->id ?? null);

        if ($Trainer) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المدربين',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف المدربين",
            ]);
            // =================================
            $Trainer->delete();
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
        $fileName = 'المدربين_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new TrainersExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - TrainerTcpdfExportController for TCPDF export
    // - TrainerPrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = TrainerModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = TrainerModel::count();
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

        $headers = ['ID', 'اسم المدرب', 'مؤسسة المدرب', 'التحصيل العلمي', 'المجال التدريبي', 'رقم الهاتف', 'البريد الالكتروني', 'ملاحظات'];
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
        $items = TrainerModel::whereIn('id', $this->selectedRows)->get();
        foreach ($items as $item) {

            $data = [$item->id, $item->trainer_name, $item->institution_id, $item->ed_level_id, $item->domain_id, $item->phone, $item->email, $item->notes];
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

        $fileName = 'trainers_' . date('Y-m-d_H-i-s') . '.xlsx';
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