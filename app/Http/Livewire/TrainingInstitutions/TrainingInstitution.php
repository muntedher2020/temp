<?php

namespace App\Http\Livewire\TrainingInstitutions;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrainingInstitutionsExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\TrainingInstitutions\TrainingInstitutions as TrainingInstitutionModel;
use App\Models\System\ModuleField;

class TrainingInstitution extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $TrainingInstitutions = [];
    public $traininginstitution;
    public $name;
    public $search = ['name' => ''];
    public $selectedRows = [];
    public $selectAll = false;

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['name'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
    }

    public function render()
    {

        $nameSearch = '%' . $this->search['name'] . '%';
        $TrainingInstitutions = TrainingInstitutionModel::query()
            ->when($this->search['name'], function ($query) use ($nameSearch) {
                $query->where('name', 'LIKE', $nameSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $TrainingInstitutions;
        $this->TrainingInstitutions = collect($TrainingInstitutions->items());

        return view('livewire.training-institutions.training-institution', [
            'TrainingInstitutions' => $TrainingInstitutions,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('TrainingInstitutions', false);
            return $rules ?: [
                'name' => 'required|unique:training_institutions,name|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:training_institutions,name|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('TrainingInstitutions', true, $this->traininginstitution->id ?? null);
            return $rules ?: [
                'name' => 'required|unique:training_institutions,name,' . ($this->traininginstitution->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:training_institutions,name,' . ($this->traininginstitution->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('TrainingInstitutions');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'name.required' => 'يرجى إدخال اسم المؤسسة',
            'name.unique' => 'اسم المؤسسة موجود بالفعل',
            'name.max' => 'اسم المؤسسة يجب أن يكون أقل من 255 حرف',
            'name.regex' => 'اسم المؤسسة يجب أن يحتوي على أحرف عربية فقط'
        ];
    }

    public function AddTrainingInstitutionModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('TrainingInstitutionModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            TrainingInstitutionModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'name' => $this->name
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مؤسسة المدرب',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة مؤسسة المدرب جديد",
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

    public function GetTrainingInstitution($traininginstitutionId)
    {
        $this->resetValidation();

        $this->traininginstitution  = TrainingInstitutionModel::find($traininginstitutionId);
        $this->name = $this->traininginstitution->name;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('traininginstitutionDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $TrainingInstitution = TrainingInstitutionModel::find($this->traininginstitution->id ?? null);
            if (!$TrainingInstitution) {
                $this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            $updateData = [
                'user_id' => Auth::user()->id,
            'name' => $this->name
            ];

            

            $TrainingInstitution->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مؤسسة المدرب',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل مؤسسة المدرب",
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
        $TrainingInstitution = TrainingInstitutionModel::find($this->traininginstitution->id ?? null);

        if ($TrainingInstitution) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'مؤسسة المدرب',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف مؤسسة المدرب",
            ]);
            // =================================
            $TrainingInstitution->delete();
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
        $fileName = 'مؤسسة المدرب_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new TrainingInstitutionsExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - TrainingInstitutionTcpdfExportController for TCPDF export
    // - TrainingInstitutionPrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = TrainingInstitutionModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = TrainingInstitutionModel::count();
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

        $headers = ['ID', 'اسم المؤسسة'];
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
        $items = TrainingInstitutionModel::whereIn('id', $this->selectedRows)->get();
        foreach ($items as $item) {

            $data = [$item->id, $item->name];
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

        $fileName = 'traininginstitutions_' . date('Y-m-d_H-i-s') . '.xlsx';
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