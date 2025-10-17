<?php

namespace App\Http\Livewire\EducationalLevels;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EducationalLevelsExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\EducationalLevels\EducationalLevels as EducationalLevelModel;
use App\Models\System\ModuleField;

class EducationalLevel extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $EducationalLevels = [];
    public $educationallevel;
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
        $EducationalLevels = EducationalLevelModel::query()
            ->when($this->search['name'], function ($query) use ($nameSearch) {
                $query->where('name', 'LIKE', $nameSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $EducationalLevels;
        $this->EducationalLevels = collect($EducationalLevels->items());

        return view('livewire.educational-levels.educational-level', [
            'EducationalLevels' => $EducationalLevels,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('EducationalLevels', false);
            return $rules ?: [
                'name' => 'required|unique:educational_levels,name|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:educational_levels,name|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('EducationalLevels', true, $this->educationallevel->id ?? null);
            return $rules ?: [
                'name' => 'required|unique:educational_levels,name,' . ($this->educationallevel->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:educational_levels,name,' . ($this->educationallevel->id ?? null) . ',id|max:255|regex:/^[\p{Arabic}\s]+$/u'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('EducationalLevels');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'name.required' => 'يرجى إدخال التحصيل العلمي',
            'name.unique' => 'التحصيل العلمي موجود بالفعل',
            'name.max' => 'التحصيل العلمي يجب أن يكون أقل من 255 حرف',
            'name.regex' => 'التحصيل العلمي يجب أن يحتوي على أحرف عربية فقط'
        ];
    }

    public function AddEducationalLevelModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('EducationalLevelModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            EducationalLevelModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'name' => $this->name
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'التحصيل العلمي',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة التحصيل العلمي جديد",
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

    public function GetEducationalLevel($educationallevelId)
    {
        $this->resetValidation();

        $this->educationallevel  = EducationalLevelModel::find($educationallevelId);
        $this->name = $this->educationallevel->name;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('educationallevelDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $EducationalLevel = EducationalLevelModel::find($this->educationallevel->id ?? null);
            if (!$EducationalLevel) {
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

            

            $EducationalLevel->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'التحصيل العلمي',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل التحصيل العلمي",
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
        $EducationalLevel = EducationalLevelModel::find($this->educationallevel->id ?? null);

        if ($EducationalLevel) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'التحصيل العلمي',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف التحصيل العلمي",
            ]);
            // =================================
            $EducationalLevel->delete();
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
        $fileName = 'التحصيل العلمي_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new EducationalLevelsExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - EducationalLevelTcpdfExportController for TCPDF export
    // - EducationalLevelPrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = EducationalLevelModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = EducationalLevelModel::count();
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

        $headers = ['ID', 'التحصيل العلمي'];
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
        $items = EducationalLevelModel::whereIn('id', $this->selectedRows)->get();
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

        $fileName = 'educationallevels_' . date('Y-m-d_H-i-s') . '.xlsx';
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