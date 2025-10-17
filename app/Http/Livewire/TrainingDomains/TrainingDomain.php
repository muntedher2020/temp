<?php

namespace App\Http\Livewire\TrainingDomains;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tracking\Tracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrainingDomainsExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\TrainingDomains\TrainingDomains as TrainingDomainModel;
use App\Models\System\ModuleField;

class TrainingDomain extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $TrainingDomains = [];
    public $trainingdomain;
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
        $TrainingDomains = TrainingDomainModel::query()
            ->when($this->search['name'], function ($query) use ($nameSearch) {
                $query->where('name', 'LIKE', $nameSearch);
            })

            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $TrainingDomains;
        $this->TrainingDomains = collect($TrainingDomains->items());

        return view('livewire.training-domains.training-domain', [
            'TrainingDomains' => $TrainingDomains,
            'links' => $links,
            '_instance' => $this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            $rules = ModuleField::getValidationRules('TrainingDomains', false);
            return $rules ?: [
                'name' => 'required|unique:training_domains,name|max:255'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:training_domains,name|max:255'
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            $rules = ModuleField::getValidationRules('TrainingDomains', true, $this->trainingdomain->id ?? null);
            return $rules ?: [
                'name' => 'required|unique:training_domains,name,' . ($this->trainingdomain->id ?? null) . ',id|max:255'
            ];
        } catch (\Exception $e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                'name' => 'required|unique:training_domains,name,' . ($this->trainingdomain->id ?? null) . ',id|max:255'
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            $messages = ModuleField::getValidationMessages('TrainingDomains');
            return $messages ?: $this->getFallbackMessages();
        } catch (\Exception $e) {
            return $this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            'name.required' => 'يرجى إدخال اسم المجال',
            'name.unique' => 'اسم المجال موجود بالفعل',
            'name.max' => 'اسم المجال يجب أن يكون أقل من 255 حرف'
        ];
    }

    public function AddTrainingDomainModalShow()
    {
        $this->reset();
        $this->resetValidation();
        $this->dispatchBrowserEvent('TrainingDomainModalShow');
    }


    public function store()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getStoreRules(), $this->getValidationMessages());

            // Handle file uploads
            $fileData = [];
            

            TrainingDomainModel::create(array_merge([
                'user_id' => Auth::user()->id,
            'name' => $this->name
            ], $fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المجال التدريبي',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم اضافة المجال التدريبي جديد",
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

    public function GetTrainingDomain($trainingdomainId)
    {
        $this->resetValidation();

        $this->trainingdomain  = TrainingDomainModel::find($trainingdomainId);
        $this->name = $this->trainingdomain->name;



        // Dispatch event to notify frontend that data is loaded
        $this->dispatchBrowserEvent('trainingdomainDataLoaded');
    }

    public function update()
    {
        try {
            $this->resetValidation();
            $this->validate($this->getUpdateRules(), $this->getValidationMessages());

            $TrainingDomain = TrainingDomainModel::find($this->trainingdomain->id ?? null);
            if (!$TrainingDomain) {
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

            

            $TrainingDomain->update($updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المجال التدريبي',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم تعديل المجال التدريبي",
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
        $TrainingDomain = TrainingDomainModel::find($this->trainingdomain->id ?? null);

        if ($TrainingDomain) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => 'المجال التدريبي',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => "تم حذف المجال التدريبي",
            ]);
            // =================================
            $TrainingDomain->delete();
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
        $fileName = 'المجال التدريبي_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new TrainingDomainsExport, $fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - TrainingDomainTcpdfExportController for TCPDF export
    // - TrainingDomainPrintController for direct printing

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRows = TrainingDomainModel::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows($value)
    {
        $totalCount = TrainingDomainModel::count();
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

        $headers = ['ID', 'اسم المجال'];
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
        $items = TrainingDomainModel::whereIn('id', $this->selectedRows)->get();
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

        $fileName = 'trainingdomains_' . date('Y-m-d_H-i-s') . '.xlsx';
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