<?php

namespace App\Exports\DataManagement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnFormatting,
    WithProperties,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    protected $tableName;
    protected $selectedColumns;
    protected $conditions;
    protected $limit;
    protected $isTemplate;
    protected $customHeaders;

    public function __construct(
        string $tableName,
        array $selectedColumns = [],
        array $conditions = [],
        int $limit = null,
        bool $isTemplate = false,
        array $customHeaders = []
    ) {
        $this->tableName = $tableName;
        $this->selectedColumns = empty($selectedColumns) ? Schema::getColumnListing($tableName) : $selectedColumns;
        $this->conditions = $conditions;
        $this->limit = $limit;
        $this->isTemplate = $isTemplate;
        $this->customHeaders = $customHeaders;
    }

    /**
     * جمع البيانات للتصدير
     */
    public function collection()
    {
        if ($this->isTemplate) {
            // إرجاع مجموعة فارغة للقالب
            return collect([]);
        }

        $query = DB::table($this->tableName)->select($this->selectedColumns);

        // تطبيق الشروط
        foreach ($this->conditions as $condition) {
            if (isset($condition['column'], $condition['operator'], $condition['value'])) {
                $query->where($condition['column'], $condition['operator'], $condition['value']);
            }
        }

        // تطبيق الحد الأقصى للسجلات
        if ($this->limit) {
            $query->limit($this->limit);
        }

        // ترتيب البيانات
        $query->orderBy('id', 'desc');

        $data = $query->get();

        // تحويل البيانات لتكون مناسبة للعرض
        return $data->map(function ($row) {
            $formattedRow = [];
            foreach ($this->selectedColumns as $column) {
                $value = $row->{$column} ?? '';

                // تنسيق البيانات حسب نوع العمود
                $formattedRow[$column] = $this->formatCellValue($value, $column);
            }
            return $formattedRow;
        });
    }

    /**
     * عناوين الأعمدة
     */
    public function headings(): array
    {
        if (!empty($this->customHeaders)) {
            return array_values($this->customHeaders);
        }

        // إرجاع أسماء الحقول الإنجليزية مباشرة (لسهولة الاستيراد)
        return $this->selectedColumns;
    }

    /**
     * تنسيق الخلايا
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = chr(64 + count($this->selectedColumns));
        $lastRow = $this->isTemplate ? 1 : ($this->collection()->count() + 1);

        return [
            // تنسيق عام للورقة
            'A1:' . $lastColumn . $lastRow => [
                'font' => [
                    'name' => 'Arial',
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'readOrder' => Alignment::READORDER_RTL,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
            // تنسيق العناوين
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '366092'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * تنسيق أنواع الأعمدة
     */
    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->selectedColumns as $index => $column) {
            $columnLetter = chr(65 + $index);

            // تنسيق حسب نوع البيانات
            if (str_contains($column, 'date') || str_contains($column, 'time')) {
                $formats[$columnLetter] = 'yyyy-mm-dd hh:mm:ss';
            } elseif (str_contains($column, 'price') || str_contains($column, 'amount')) {
                $formats[$columnLetter] = '#,##0.00';
            } elseif (str_contains($column, 'phone') || str_contains($column, 'mobile')) {
                $formats[$columnLetter] = '@'; // نص
            }
        }

        return $formats;
    }

    /**
     * خصائص الملف
     */
    public function properties(): array
    {
        return [
            'creator' => 'نظام إدارة البيانات',
            'lastModifiedBy' => auth()->user()->name ?? 'النظام',
            'title' => "تصدير بيانات جدول: {$this->tableName}",
            'description' => 'ملف تصدير تم إنشاؤه بواسطة نظام إدارة البيانات',
            'subject' => "بيانات {$this->tableName}",
            'keywords' => 'تصدير,بيانات,إكسل',
            'category' => 'تقارير البيانات',
            'manager' => 'نظام إدارة البيانات',
            'company' => config('app.name', 'Laravel'),
        ];
    }

    /**
     * عنوان ورقة العمل
     */
    public function title(): string
    {
        return $this->getTableDisplayName($this->tableName);
    }

    /**
     * عرض الأعمدة
     */
    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->selectedColumns as $index => $column) {
            $columnLetter = chr(65 + $index);

            // تحديد عرض العمود حسب نوع البيانات
            if (str_contains($column, 'id')) {
                $widths[$columnLetter] = 10;
            } elseif (str_contains($column, 'email')) {
                $widths[$columnLetter] = 25;
            } elseif (str_contains($column, 'phone') || str_contains($column, 'mobile')) {
                $widths[$columnLetter] = 15;
            } elseif (str_contains($column, 'date') || str_contains($column, 'time')) {
                $widths[$columnLetter] = 18;
            } elseif (str_contains($column, 'description') || str_contains($column, 'content')) {
                $widths[$columnLetter] = 40;
            } else {
                $widths[$columnLetter] = 20;
            }
        }

        return $widths;
    }

    /**
     * أحداث إضافية
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // إعداد اتجاه النص للعربية
                $event->sheet->getDelegate()->setRightToLeft(true);

                // تجميد الصف الأول (العناوين)
                $event->sheet->getDelegate()->freezePane('A2');

                // إضافة فلتر تلقائي
                if (!$this->isTemplate) {
                    $lastColumn = chr(64 + count($this->selectedColumns));
                    $lastRow = $this->collection()->count() + 1;
                    $event->sheet->getDelegate()->setAutoFilter("A1:{$lastColumn}{$lastRow}");
                }
            },
        ];
    }

    /**
     * تنسيق قيمة الخلية
     */
    private function formatCellValue($value, $column)
    {
        // إذا كانت القيمة فارغة
        if (is_null($value) || $value === '') {
            return '-';
        }

        // تنسيق التواريخ
        if (str_contains($column, 'date') || str_contains($column, 'time')) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // تنسيق القيم المنطقية
        if (is_bool($value) || in_array($value, [0, 1, '0', '1'])) {
            return $value == 1 ? 'نشط' : 'غير نشط';
        }

        // تنسيق الأرقام الكبيرة
        if (is_numeric($value) && $value > 1000) {
            return number_format($value, 2);
        }

        return $value;
    }

    /**
     * الحصول على الاسم العربي للعمود
     */
    private function getArabicColumnName($column)
    {
        $translations = [
            'id' => 'المعرف',
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'mobile' => 'الجوال',
            'address' => 'العنوان',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'deleted_at' => 'تاريخ الحذف',
            'description' => 'الوصف',
            'title' => 'العنوان',
            'content' => 'المحتوى',
            'category' => 'الفئة',
            'type' => 'النوع',
            'price' => 'السعر',
            'amount' => 'المبلغ',
            'quantity' => 'الكمية',
            'date' => 'التاريخ',
            'time' => 'الوقت',
            'user_id' => 'المستخدم',
            'role_id' => 'الدور',
            'permission_id' => 'الصلاحية',
            'group_id' => 'المجموعة',
            'parent_id' => 'العنصر الأب',
            'sort_order' => 'ترتيب العرض',
            'is_active' => 'نشط',
            'is_default' => 'افتراضي',
            'icon' => 'الأيقونة',
            'color' => 'اللون',
            'code' => 'الكود',
            'slug' => 'الرابط المختصر'
        ];

        return $translations[$column] ?? ucfirst(str_replace('_', ' ', $column));
    }

    /**
     * الحصول على اسم عرض الجدول
     */
    private function getTableDisplayName($tableName)
    {
        $translations = [
            'users' => 'المستخدمين',
            'basic_groups' => 'المجموعات الأساسية',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'trackings' => 'سجل التتبع',
            'online_sessions' => 'الجلسات النشطة',
            'data_templates' => 'قوالب البيانات',
            'data_template_usages' => 'سجل استخدام القوالب'
        ];

        return $translations[$tableName] ?? ucfirst(str_replace('_', ' ', $tableName));
    }
}
