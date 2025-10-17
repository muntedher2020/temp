<?php

namespace App\Http\Livewire\ModuleGenerator;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicMenuService;
use App\Models\Management\BasicGroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class ModuleGenerator extends Component
{
    public $moduleName = '';
    public $moduleArName = '';
    public $fields = [];

    // نوع الوحدة
    public $moduleType = 'item'; // 'item' للعناصر (تحت مجموعة أو مستقلة)، 'group' للمجموعة الأب

    // خيارات المجموعة الأصلية للعنصر
    public $parentGroup = ''; // المجموعة الأصلية للعناصر الفرعية (فارغ للعناصر المستقلة)
    public $availableGroups = []; // المجموعات المتاحة

    // خيارات المجموعة الأب الجديدة (عند إنشاء group)
    public $parentGroupIcon = 'mdi mdi-folder-outline';
    public $parentGroupOrder = 1;

    // خيارات أيقونة العنصر (عند إنشاء item)
    public $itemIcon = 'mdi mdi-circle';

    // نظام اختيار الأيقونات
    public $iconPreview = '';
    public $showIconPicker = false;

    // قوائم الجداول والحقول لحقول العلاقات
    public $availableTables = []; // الجداول المتاحة في قاعدة البيانات
    public $selectedTableColumns = []; // حقول الجدول المختار

    // خيارات الميزات المتقدمة
    public $enableExcelExport = true;
    public $enablePdfExport = true;
    public $enableFlatpickr = true;
    public $enableSelect2 = true;

    public $newField = [
        'name' => '',
        'ar_name' => '',
        'type' => 'string',
        'required' => true,
        'unique' => false,
        'searchable' => true,
        'show_in_table' => true, // ظهور في جدول العرض
        'show_in_search' => true, // ظهور في رأس البحث
        'show_in_forms' => true, // ظهور في نوافذ الإضافة والتعديل
        'size' => '',
        'arabic_only' => false,
        'numeric_only' => false,
        // إعدادات النص الجديدة
        'text_content_type' => 'any', // any, arabic_only, numeric_only, english_only
        // إعدادات الأرقام الصحيحة الجديدة
        'integer_type' => 'int', // int, bigint, smallint, tinyint
        'unsigned' => false,
        // إعدادات الأرقام العشرية الجديدة
        'decimal_precision' => 15,
        'decimal_scale' => 2,
        'file_types' => '',
        'select_options' => [],
        'select_source' => 'manual',
        'select_numeric_values' => false, // قيم رقمية في القائمة المنسدلة
        'related_table' => '',
        'related_key' => 'id',
        'related_display' => 'name',
        'checkbox_true_label' => 'نعم',
        'checkbox_false_label' => 'لا',
        'is_calculated' => false, // حقل محسوب
        'calculation_formula' => '', // معادلة الحساب
        'calculation_type' => 'none', // نوع الحساب: none, formula, date_diff
        'date_from_field' => '', // الحقل المرجعي للتاريخ من
        'date_to_field' => '', // الحقل المرجعي للتاريخ إلى
        'date_diff_unit' => 'days', // وحدة قياس الفرق
        'include_end_date' => false, // شمل التاريخ النهائي
        'absolute_value' => false, // قيمة مطلقة
        'remaining_only' => false, // الأيام المتبقية فقط
        'is_date_calculated' => false, // هل الحقل محسوب للتاريخ
        'date_calculation_config' => null, // إعدادات حساب التاريخ
        'time_from_field' => '', // الحقل المرجعي للوقت من
        'time_to_field' => '', // الحقل المرجعي للوقت إلى
        'time_diff_unit' => 'minutes', // وحدة قياس فرق الوقت
        'is_time_calculated' => false, // هل الحقل محسوب للوقت
        'time_calculation_config' => null // إعدادات حساب الوقت
    ];

    public $fieldTypes = [
        'string' => 'نص',
        'text' => 'نص طويل',
        'integer' => 'رقم صحيح',
        'email' => 'بريد إلكتروني',
        'date' => 'تاريخ',
        'datetime' => 'تاريخ ووقت',
        'time' => 'وقت فقط',
        'month_year' => 'شهر / سنة',
        'checkbox' => 'صح/خطأ',
        'file' => 'ملف',
        'select' => 'قائمة منسدلة',
        'decimal' => 'رقم عشري'
    ];

    public function mount()
    {
        // تفعيل الحالة الأولية للحقل الافتراضي
        $this->initializeFieldType();

        // تحديث قائمة المجموعات المتاحة
        $this->updateAvailableGroups();

        // تحميل الجداول المتاحة
        $this->loadAvailableTables();

        // تهيئة معاينة الأيقونة
        $this->iconPreview = $this->parentGroupIcon;
    }

    /**
     * تحديث قائمة المجموعات المتاحة من القائمة الديناميكية
     */
    public function updateAvailableGroups()
    {
        try {
            $configPath = config_path('dynamic-menu.php');
            if (file_exists($configPath)) {
                $config = include $configPath;
                $this->availableGroups = [];

                if (isset($config['menu_items']) && is_array($config['menu_items'])) {
                    foreach ($config['menu_items'] as $item) {
                        if ($item['type'] === 'group') {
                            $this->availableGroups[] = [
                                'value' => $item['permission'],
                                'label' => $item['title']
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نترك القائمة فارغة
            $this->availableGroups = [];
        }
    }

    /**
     * دالة تستدعى عند تحديد المجموعة الأصلية
     */
    public function updatedParentGroup()
    {
        // يمكن إضافة منطق إضافي هنا عند الحاجة
    }

    /**
     * دالة تستدعى عند تغيير نوع الوحدة
     */
    public function updatedModuleType()
    {
        if ($this->moduleType === 'group') {
            // تعيين القيم الافتراضية للمجموعة الأساسية الجديدة
            if (empty($this->parentGroupIcon)) {
                $this->parentGroupIcon = 'mdi mdi-folder-outline';
            }
            if (empty($this->parentGroupOrder)) {
                $this->parentGroupOrder = BasicGroup::getSuggestedSortOrder();
            }
            // تحديث معاينة الأيقونة
            $this->iconPreview = $this->parentGroupIcon;
        }
    }

    /**
     * دالة تستدعى عند تغيير أيقونة المجموعة الأساسية
     */
    public function updatedParentGroupIcon()
    {
        $this->iconPreview = $this->parentGroupIcon;
    }

    /**
     * تحميل الجداول المتاحة في قاعدة البيانات
     */
    public function loadAvailableTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $this->availableTables = [];

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                // تجاهل جداول النظام
                if (!in_array($tableName, ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'])) {
                    $this->availableTables[] = $tableName;
                }
            }
        } catch (\Exception $e) {
            $this->availableTables = [];
            Log::error('خطأ في تحميل الجداول: ' . $e->getMessage());
        }
    }

    /**
     * تحميل حقول الجدول المختار
     */
    public function loadTableColumns($tableName)
    {
        try {
            if (empty($tableName)) {
                $this->selectedTableColumns = [];
                return [];
            }

            $columns = Schema::getColumnListing($tableName);
            $this->selectedTableColumns = array_filter($columns, function($column) {
                // تجاهل الحقول الافتراضية
                return !in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at']);
            });

            // إعادة تعيين القيم المختارة عند تغيير الجدول
            $this->newField['related_key'] = 'id';
            $this->newField['related_display'] = '';

            return $this->selectedTableColumns;
        } catch (\Exception $e) {
            $this->selectedTableColumns = [];
            Log::error("خطأ في تحميل حقول الجدول {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * وظيفة مساعدة للحصول على حقول جدول معين (تستخدم من JavaScript)
     */
    public function getTableColumns($tableName)
    {
        return $this->loadTableColumns($tableName);
    }

    public function render()
    {
        return view('livewire.module-generator.module-generator');
    }

    // دالة مخصصة لتفعيل نوع الحقل - تُستدعى من mount
    private function initializeFieldType()
    {
        // نفس منطق updatedNewFieldType لكن بدون تداخل lifecycle
        if ($this->newField['type'] === 'checkbox') {
            $this->newField['required'] = false;
        }

        if (in_array($this->newField['type'], ['date', 'datetime', 'time', 'month_year'])) {
            $this->enableFlatpickr = true;
        }

        if ($this->newField['type'] === 'select') {
            $this->enableSelect2 = true;
        }
    }

    // دالة مخصصة لتغيير نوع الحقل - تُستدعى من wire:change
    public function changeFieldType()
    {
        // استدعاء دالة updatedNewFieldType بأمان
        $this->updatedNewFieldType();

        // إعادة رسم الواجهة للتأكد من ظهور الخصائص الجديدة
        $this->render();
    }

    public function addField()
    {
        $validationRules = [
            'newField.name' => 'required|string|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            'newField.ar_name' => 'required|string',
            'newField.type' => 'required|in:string,text,integer,email,date,datetime,time,month_year,checkbox,file,select,decimal',
        ];

        $validationMessages = [
            'newField.name.required' => 'اسم الحقل مطلوب',
            'newField.name.regex' => 'اسم الحقل يجب أن يكون بالإنجليزية فقط',
            'newField.ar_name.required' => 'الاسم العربي مطلوب',
            'newField.type.required' => 'نوع الحقل مطلوب',
            'newField.type.in' => 'نوع الحقل المحدد غير صالح',
        ];

        // إضافة validation للحقول المحسوبة
        if ($this->newField['is_calculated'] ?? false) {
            // التأكد أن الحقل رقمي (integer, decimal, أو select رقمي)
            if (!in_array($this->newField['type'], ['integer', 'decimal']) &&
                !($this->newField['type'] === 'select' && isset($this->newField['select_numeric_values']) && $this->newField['select_numeric_values'] == true)) {
                $this->addError('newField.type', 'الحقول المحسوبة يجب أن تكون من نوع رقم صحيح أو عشري أو قائمة منسدلة رقمية');
                return;
            }

            // التحقق من نوع الحساب
            $calculationType = $this->newField['calculation_type'] ?? 'none';

            if ($calculationType === 'formula') {
                // التأكد من وجود معادلة
                if (empty($this->newField['calculation_formula'])) {
                    $this->addError('newField.calculation_formula', 'معادلة الحساب مطلوبة للحقول المحسوبة');
                    return;
                }

                // التحقق من صحة المعادلة
                if (!$this->validateCalculationFormula($this->newField['calculation_formula'])) {
                    $this->addError('newField.calculation_formula', 'معادلة الحساب غير صحيحة أو تحتوي على حقول غير موجودة');
                    return;
                }
            } elseif ($calculationType === 'date_diff') {
                // التحقق من حقول التاريخ
                if (empty($this->newField['date_from_field'])) {
                    $this->addError('newField.date_from_field', 'حقل التاريخ من مطلوب لحساب فرق التواريخ');
                    return;
                }

                if (empty($this->newField['date_to_field'])) {
                    $this->addError('newField.date_to_field', 'حقل التاريخ إلى مطلوب لحساب فرق التواريخ');
                    return;
                }

                // التحقق من أن حقول التاريخ موجودة في الحقول المضافة
                $dateFields = collect($this->fields)->whereIn('type', ['date', 'datetime'])->pluck('name')->toArray();

                if (!in_array($this->newField['date_from_field'], $dateFields)) {
                    $this->addError('newField.date_from_field', 'حقل التاريخ من يجب أن يكون من الحقول المضافة من نوع تاريخ أو تاريخ ووقت');
                    return;
                }

                if (!in_array($this->newField['date_to_field'], $dateFields)) {
                    $this->addError('newField.date_to_field', 'حقل التاريخ إلى يجب أن يكون من الحقول المضافة من نوع تاريخ أو تاريخ ووقت');
                    return;
                }

                // تعيين is_date_calculated إلى true
                $this->newField['is_date_calculated'] = true;
            } elseif ($calculationType === 'time_diff') {
                // التحقق من حقول الوقت
                if (empty($this->newField['time_from_field'])) {
                    $this->addError('newField.time_from_field', 'حقل الوقت من مطلوب لحساب فرق الأوقات');
                    return;
                }

                if (empty($this->newField['time_to_field'])) {
                    $this->addError('newField.time_to_field', 'حقل الوقت إلى مطلوب لحساب فرق الأوقات');
                    return;
                }

                // التحقق من أن حقول الوقت موجودة في الحقول المضافة
                $timeFields = collect($this->fields)->whereIn('type', ['time', 'datetime'])->pluck('name')->toArray();

                if (!in_array($this->newField['time_from_field'], $timeFields)) {
                    $this->addError('newField.time_from_field', 'حقل الوقت من يجب أن يكون من الحقول المضافة من نوع وقت أو تاريخ ووقت');
                    return;
                }

                if (!in_array($this->newField['time_to_field'], $timeFields)) {
                    $this->addError('newField.time_to_field', 'حقل الوقت إلى يجب أن يكون من الحقول المضافة من نوع وقت أو تاريخ ووقت');
                    return;
                }

                // تعيين is_time_calculated إلى true
                $this->newField['is_time_calculated'] = true;
            }
        }

        $this->validate($validationRules, $validationMessages);

        // Check if field name already exists
        foreach ($this->fields as $field) {
            if ($field['name'] === $this->newField['name']) {
                $this->addError('newField.name', 'اسم الحقل موجود بالفعل');
                return;
            }
        }

        $this->fields[] = $this->newField;

        $this->resetField();
        $this->dispatchBrowserEvent('success', [
            'message' => 'تم إضافة الحقل بنجاح',
            'title' => 'إضافة حقل'
        ]);
    }

    public function removeField($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);

        $this->dispatchBrowserEvent('success', [
            'message' => 'تم حذف الحقل بنجاح',
            'title' => 'حذف حقل'
        ]);
    }

    public function generateModule()
    {
        // تنظيف اسم الوحدة من المسافات الزائدة أو الحروف الخفية
        $this->moduleName = trim($this->moduleName);

        // تحقق من البيانات الأساسية
        $validationRules = [
            'moduleName' => 'required|string|regex:/^[A-Za-z][A-Za-z0-9]*$/u',
            'moduleArName' => 'required|string',
            'fields' => 'required|array|min:1',
            'moduleType' => 'required|in:item,group',
        ];

        $validationMessages = [
            'moduleName.required' => 'اسم الوحدة مطلوب',
            'moduleName.regex' => 'اسم الوحدة يجب أن يكون بالإنجليزية فقط (بدون مسافات أو رموز خاصة)',
            'moduleArName.required' => 'الاسم العربي للوحدة مطلوب',
            'fields.required' => 'يجب إضافة حقل واحد على الأقل',
            'fields.min' => 'يجب إضافة حقل واحد على الأقل',
            'moduleType.required' => 'يجب اختيار نوع الوحدة',
        ];

        // إضافة validation خاص بنوع الوحدة
        if ($this->moduleType === 'item' && !empty($this->parentGroup)) {
            // العنصر تحت مجموعة - يجب اختيار المجموعة الأصلية
            $validationRules['parentGroup'] = 'required|string';
            $validationMessages['parentGroup.required'] = 'يجب اختيار المجموعة الأصلية للعنصر';
        } elseif ($this->moduleType === 'group') {
            // مجموعة جديدة - يجب تحديد الأيقونة والترتيب
            $validationRules['parentGroupIcon'] = 'required|string';
            $validationRules['parentGroupOrder'] = 'required|integer|min:0';
            $validationMessages['parentGroupIcon.required'] = 'أيقونة المجموعة مطلوبة';
            $validationMessages['parentGroupOrder.required'] = 'ترتيب العرض مطلوب';
            $validationMessages['parentGroupOrder.integer'] = 'ترتيب العرض يجب أن يكون رقماً صحيحاً';
            $validationMessages['parentGroupOrder.min'] = 'ترتيب العرض يجب أن يكون أكبر من أو يساوي 0';
        }

        $this->validate($validationRules, $validationMessages);

        try {
            // تحضير بيانات الحقول بالتنسيق الصحيح
            $processedFields = [];
            foreach ($this->fields as $field) {
                $processedField = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'ar_name' => $field['ar_name'],
                    'arabic_label' => $field['ar_name'], // للتوافق مع النسخة القديمة
                    'required' => $field['required'] ?? true,
                    'searchable' => $field['searchable'] ?? true,
                    'show_in_table' => $field['show_in_table'] ?? true, // ← إضافة جديدة
                    'show_in_search' => $field['show_in_search'] ?? true, // ← إضافة جديدة
                    'show_in_forms' => $field['show_in_forms'] ?? true, // ← إضافة جديدة
                ];

                // إضافة الخصائص الاختيارية
                if ($field['unique'] ?? false) {
                    $processedField['unique'] = true;
                }

                if (!empty($field['size'])) {
                    $processedField['size'] = (int) $field['size'];
                }

                if ($field['arabic_only'] ?? false) {
                    $processedField['arabic_only'] = true;
                }

                if ($field['numeric_only'] ?? false) {
                    $processedField['numeric_only'] = true;
                }

                // إضافة إعدادات النص الجديدة
                if (!empty($field['text_content_type'])) {
                    $processedField['text_content_type'] = $field['text_content_type'];
                }

                // إضافة إعدادات الأرقام الصحيحة الجديدة
                if ($field['type'] === 'integer') {
                    $processedField['integer_type'] = $field['integer_type'] ?? 'int';
                    $processedField['unsigned'] = $field['unsigned'] ?? false;
                }

                // إضافة إعدادات الأرقام العشرية الجديدة
                if ($field['type'] === 'decimal') {
                    $processedField['decimal_precision'] = $field['decimal_precision'] ?? 15;
                    $processedField['decimal_scale'] = $field['decimal_scale'] ?? 2;
                }

                // معالجة حقول القائمة المنسدلة
                if ($field['type'] === 'select') {
                    $processedField['select_source'] = $field['select_source'] ?? 'manual';
                    $processedField['select_numeric_values'] = $field['select_numeric_values'] ?? false;

                    if ($processedField['select_source'] === 'manual' && !empty($field['select_options'])) {
                        $processedField['select_options'] = $field['select_options'];
                    } elseif ($processedField['select_source'] === 'database') {
                        if (!empty($field['related_table'])) {
                            $processedField['related_table'] = $field['related_table'];
                            $processedField['related_key'] = $field['related_key'] ?? 'id';
                            $processedField['related_display'] = $field['related_display'] ?? 'name';
                        }
                    }
                }

                // معالجة حقول الملفات
                if ($field['type'] === 'file' && !empty($field['file_types'])) {
                    $processedField['file_types'] = $field['file_types'];
                }

                // معالجة حقول الـ checkbox
                if ($field['type'] === 'checkbox') {
                    $processedField['checkbox_true_label'] = $field['checkbox_true_label'] ?? 'نعم';
                    $processedField['checkbox_false_label'] = $field['checkbox_false_label'] ?? 'لا';
                }

                // معالجة الحقول المحسوبة
                if ($field['is_calculated'] ?? false) {
                    $processedField['is_calculated'] = true;
                    $processedField['calculation_type'] = $field['calculation_type'] ?? 'none';

                    // حقول الحساب الرياضي
                    if ($field['calculation_type'] === 'formula') {
                        $processedField['calculation_formula'] = $field['calculation_formula'] ?? '';
                    }

                    // حقول حساب التاريخ
                    if ($field['calculation_type'] === 'date_diff') {
                        $processedField['date_from_field'] = $field['date_from_field'] ?? '';
                        $processedField['date_to_field'] = $field['date_to_field'] ?? '';
                        $processedField['date_diff_unit'] = $field['date_diff_unit'] ?? 'days';
                        $processedField['remaining_only'] = $field['remaining_only'] ?? false;
                        $processedField['is_date_calculated'] = $field['is_date_calculated'] ?? false;
                        $processedField['include_end_date'] = $field['include_end_date'] ?? false;
                        $processedField['absolute_value'] = $field['absolute_value'] ?? false;
                        $processedField['date_calculation_config'] = $field['date_calculation_config'] ?? null;
                    }

                    // حقول حساب الوقت
                    if ($field['calculation_type'] === 'time_diff') {
                        $processedField['time_from_field'] = $field['time_from_field'] ?? '';
                        $processedField['time_to_field'] = $field['time_to_field'] ?? '';
                        $processedField['time_diff_unit'] = $field['time_diff_unit'] ?? 'minutes';
                        $processedField['is_time_calculated'] = $field['is_time_calculated'] ?? false;
                        $processedField['absolute_value'] = $field['absolute_value'] ?? false;
                        $processedField['remaining_only'] = $field['remaining_only'] ?? false;
                        $processedField['time_calculation_config'] = $field['time_calculation_config'] ?? null;
                    }
                }

                $processedFields[] = $processedField;
            }

            // إنشاء ملف JSON مؤقت للحقول
            $tempFileName = 'temp_module_fields_' . time() . '.json';
            $tempFilePath = storage_path('app/' . $tempFileName);

            file_put_contents($tempFilePath, json_encode($processedFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // تحضير المعاملات
            $arguments = [
                'name' => $this->moduleName,
                '--fields-file' => $tempFilePath, // تمرير المسار الكامل بدلاً من اسم الملف فقط
                '--ar-name' => $this->moduleArName,
            ];

            // إضافة معاملات خاصة بنوع الوحدة
            if ($this->moduleType === 'item') {
                // إذا كان هناك مجموعة أب محددة، نضعها كـ sub، وإلا كـ main للعنصر المستقل
                if (!empty($this->parentGroup)) {
                    $arguments['--type'] = 'sub';
                    $arguments['--parent-group'] = $this->parentGroup;
                } else {
                    $arguments['--type'] = 'main'; // عنصر مستقل
                }

                // إضافة أيقونة العنصر إذا كانت محددة
                if (!empty($this->itemIcon) && $this->itemIcon !== 'mdi mdi-circle') {
                    $arguments['--item-icon'] = $this->itemIcon;
                }
            } elseif ($this->moduleType === 'group') {
                $arguments['--type'] = 'main'; // المجموعات تكون في المستوى الرئيسي

                // إضافة أيقونة المجموعة المختارة
                if (!empty($this->parentGroupIcon) && $this->parentGroupIcon !== 'mdi mdi-folder-outline') {
                    $arguments['--item-icon'] = $this->parentGroupIcon;
                }

                // إضافة ترتيب المجموعة
                if (!empty($this->parentGroupOrder)) {
                    $arguments['--group-order'] = $this->parentGroupOrder;
                }
            }

            // تسجيل محاولة إنشاء الوحدة
            Log::info('GUI Module Generation Attempt', [
                'module_name' => $this->moduleName,
                'ar_name' => $this->moduleArName,
                'module_type' => $this->moduleType,
                'parent_group' => ($this->moduleType === 'item' && !empty($this->parentGroup)) ? $this->parentGroup :
                                 ($this->moduleType === 'group' ? 'main' : 'standalone'),
                'fields_count' => count($processedFields),
                'temp_file' => $tempFileName,
                'arguments' => $arguments
            ]);

            // تشغيل الأمر والتحقق من النتيجة
            $exitCode = Artisan::call('make:hmvc-module', $arguments);
            $output = Artisan::output();

            // إذا كانت الوحدة من نوع group، نحتاج لإضافتها كمجموعة أب في basic_groups
            // (الأمر سيعالج إضافتها للقائمة الديناميكية تلقائياً)
            if ($this->moduleType === 'group' && $exitCode === 0) {
                // إضافة المجموعة لجدول basic_groups فقط
                // القائمة الديناميكية ستتم إدارتها بواسطة الأمر
                $this->createParentGroupInDatabase($this->moduleName);
            }

            // تسجيل نتيجة التنفيذ
            Log::info('GUI Module Generation Result', [
                'exit_code' => $exitCode,
                'output' => $output,
                'temp_file_exists' => file_exists($tempFilePath)
            ]);

            // حذف الملف المؤقت
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            // التحقق من نجاح العملية
            if ($exitCode === 0) {
                $successMessage = "تم إنشاء وحدة {$this->moduleArName} بنجاح!";

                if ($this->moduleType === 'item' && !empty($this->parentGroup)) {
                    $parentGroupName = $this->getParentGroupTitle($this->parentGroup);
                    $successMessage .= " كعنصر تحت مجموعة {$parentGroupName}";
                } elseif ($this->moduleType === 'item' && empty($this->parentGroup)) {
                    $successMessage .= " كعنصر مستقل في القائمة الرئيسية";
                } elseif ($this->moduleType === 'group') {
                    $successMessage .= " كمجموعة أب جديدة في القائمة الرئيسية";
                }

                $this->dispatchBrowserEvent('success', [
                    'message' => $successMessage,
                    'title' => 'إنشاء الوحدة'
                ]);

                // Reset form
                $this->reset(['moduleName', 'moduleArName', 'fields', 'parentGroup', 'parentGroupIcon', 'parentGroupOrder', 'itemIcon']);
                $this->moduleType = 'sub'; // إعادة تعيين نوع الوحدة للافتراضي
                $this->parentGroup = ''; // إعادة تعيين المجموعة الأصلية
                $this->updateAvailableGroups();
                $this->resetField();
            } else {
                // فشل في إنشاء الوحدة
                $errorMessage = "فشل في إنشاء الوحدة. كود الخطأ: {$exitCode}";
                if (trim($output)) {
                    $errorMessage .= "\n\nتفاصيل الخطأ:\n" . $output;
                }

                $this->dispatchBrowserEvent('error', [
                    'message' => $errorMessage,
                    'title' => 'خطأ في إنشاء الوحدة'
                ]);
            }

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء إنشاء الوحدة: ' . $e->getMessage(),
                'title' => 'خطأ'
            ]);
        }
    }

    /**
     * الحصول على عنوان المجموعة الأصلية
     */
    private function getParentGroupTitle($parentGroupValue)
    {
        foreach ($this->availableGroups as $group) {
            if ($group['value'] === $parentGroupValue) {
                return $group['label'];
            }
        }
        return $parentGroupValue;
    }

    private function resetField()
    {
        $this->newField = [
            'name' => '',
            'ar_name' => '',
            'type' => 'string',
            'required' => true,
            'unique' => false,
            'searchable' => true,
            'show_in_table' => true, // ظهور في جدول العرض
            'show_in_search' => true, // ظهور في رأس البحث
            'show_in_forms' => true, // ظهور في نوافذ الإضافة والتعديل
            'size' => '',
            'arabic_only' => false,
            'numeric_only' => false,
            // إعدادات النص الجديدة
            'text_content_type' => 'any',
            // إعدادات الأرقام الصحيحة الجديدة
            'integer_type' => 'int',
            'unsigned' => false,
            // إعدادات الأرقام العشرية الجديدة
            'decimal_precision' => 15,
            'decimal_scale' => 2,
            'file_types' => '',
            'select_options' => [],
            'select_source' => 'manual',
            'select_numeric_values' => false, // قيم رقمية في القائمة المنسدلة
            'related_table' => '',
            'related_key' => 'id',
            'related_display' => 'name',
            'checkbox_true_label' => 'نعم',
            'checkbox_false_label' => 'لا',
            'is_calculated' => false, // حقل محسوب
            'calculation_formula' => '', // معادلة الحساب
            'calculation_type' => 'none', // نوع الحساب
            'date_from_field' => '', // الحقل المرجعي للتاريخ من
            'date_to_field' => '', // الحقل المرجعي للتاريخ إلى
            'date_diff_unit' => 'days', // وحدة قياس الفرق
            'include_end_date' => false, // شمل التاريخ النهائي
            'absolute_value' => false, // قيمة مطلقة
            'remaining_only' => false, // الأيام المتبقية فقط
            'is_date_calculated' => false, // هل الحقل محسوب للتاريخ
            'date_calculation_config' => null, // إعدادات حساب التاريخ
            'time_from_field' => '', // الحقل المرجعي للوقت من
            'time_to_field' => '', // الحقل المرجعي للوقت إلى
            'time_diff_unit' => 'minutes', // وحدة قياس فرق الوقت
            'is_time_calculated' => false, // هل الحقل محسوب للوقت
            'time_calculation_config' => null // إعدادات حساب الوقت
        ];
        $this->resetValidation();

        // إعادة تعيين خيارات القائمة المنسدلة في الواجهة
        $this->dispatchBrowserEvent('clearSelectOptions');

        // إعادة تفعيل الحالة الأولية للحقل الافتراضي
        $this->initializeFieldType();
    }

    public function updatedNewFieldType()
    {
        // Reset type-specific options when type changes
        if ($this->newField['type'] !== 'string') {
            $this->newField['size'] = '';
            $this->newField['arabic_only'] = false;
        }

        if ($this->newField['type'] !== 'string' && $this->newField['type'] !== 'integer') {
            $this->newField['numeric_only'] = false;
        }

        if ($this->newField['type'] !== 'file') {
            $this->newField['file_types'] = '';
        }

        if ($this->newField['type'] !== 'select') {
            $this->newField['select_options'] = [];
            $this->newField['select_source'] = 'manual';
            $this->newField['related_table'] = '';
            $this->newField['related_key'] = 'id';
            $this->newField['related_display'] = 'name';
            // إعادة تعيين خيارات القائمة المنسدلة في الواجهة
            $this->dispatchBrowserEvent('clearSelectOptions');
        }

        if ($this->newField['type'] !== 'checkbox') {
            $this->newField['checkbox_true_label'] = 'نعم';
            $this->newField['checkbox_false_label'] = 'لا';
        }

        if ($this->newField['type'] === 'checkbox') {
            $this->newField['required'] = false;
        }

        // Auto-enable features based on field type
        if (in_array($this->newField['type'], ['date', 'datetime', 'time', 'month_year'])) {
            $this->enableFlatpickr = true;
        }

        if ($this->newField['type'] === 'select') {
            $this->enableSelect2 = true;
        }
    }

    public function addSelectOption($optionValue)
    {
        Log::info('محاولة إضافة خيار: ' . $optionValue);
        Log::info('خيارات الحقل الحالية: ', $this->newField['select_options']);

        $optionValue = trim($optionValue);
        if (!empty($optionValue) && !in_array($optionValue, $this->newField['select_options'])) {
            $this->newField['select_options'][] = $optionValue;

            Log::info('تم إضافة الخيار بنجاح. الخيارات الجديدة: ', $this->newField['select_options']);

            $this->dispatchBrowserEvent('success', [
                'message' => "تم إضافة الخيار '{$optionValue}' بنجاح",
                'title' => 'إضافة خيار'
            ]);
        } else {
            Log::warning('فشل إضافة الخيار - موجود مسبقاً أو فارغ');

            $this->dispatchBrowserEvent('error', [
                'message' => 'الخيار موجود مسبقاً أو فارغ',
                'title' => 'خطأ في الإضافة'
            ]);
        }
    }

    public function removeSelectOption($index)
    {
        if (isset($this->newField['select_options'][$index])) {
            $removedOption = $this->newField['select_options'][$index];
            unset($this->newField['select_options'][$index]);
            $this->newField['select_options'] = array_values($this->newField['select_options']);

            $this->dispatchBrowserEvent('success', [
                'message' => "تم حذف الخيار '{$removedOption}' بنجاح",
                'title' => 'حذف خيار'
            ]);
        }
    }

    public function suggestParentGroupOrder()
    {
        $this->parentGroupOrder = BasicGroup::getSuggestedSortOrder();

        $this->dispatchBrowserEvent('info', [
            'title' => 'تم الاقتراح!',
            'message' => "تم اقتراح الرقم {$this->parentGroupOrder} كترتيب عرض متاح"
        ]);
    }

    public function selectIcon($icon)
    {
        if ($this->moduleType === 'group') {
            $this->parentGroupIcon = $icon;
            $this->iconPreview = $icon;
        } else {
            $this->itemIcon = $icon;
        }
        $this->showIconPicker = false;
    }

    public function openIconPicker()
    {
        $this->showIconPicker = true;
    }

    public function closeModal()
    {
        $this->showIconPicker = false;
    }

    // نظام الأيقونات - نفس النظام المستخدم في BasicGroupManagement
    public function getIconCategories()
    {
        return [
            'عام' => [
                'mdi mdi-folder-outline',
                'mdi mdi-folder',
                'mdi mdi-home',
                'mdi mdi-office-building',
                'mdi mdi-account-group',
                'mdi mdi-cog',
                'mdi mdi-view-dashboard',
                'mdi mdi-chart-box',
                'mdi mdi-file-document',
                'mdi mdi-database',
            ],
            'أعمال' => [
                'mdi mdi-briefcase',
                'mdi mdi-currency-usd',
                'mdi mdi-chart-line',
                'mdi mdi-trending-up',
                'mdi mdi-calculator',
                'mdi mdi-receipt',
                'mdi mdi-credit-card',
                'mdi mdi-bank',
                'mdi mdi-handshake',
                'mdi mdi-store',
            ],
            'أشخاص' => [
                'mdi mdi-account',
                'mdi mdi-account-multiple',
                'mdi mdi-account-group',
                'mdi mdi-account-tie',
                'mdi mdi-account-supervisor',
                'mdi mdi-human-greeting',
                'mdi mdi-face-agent',
                'mdi mdi-badge-account',
                'mdi mdi-id-card',
                'mdi mdi-contacts',
            ],
            'تقنية' => [
                'mdi mdi-laptop',
                'mdi mdi-server',
                'mdi mdi-code-tags',
                'mdi mdi-web',
                'mdi mdi-database-settings',
                'mdi mdi-api',
                'mdi mdi-cloud',
                'mdi mdi-monitor',
                'mdi mdi-cellphone',
                'mdi mdi-wifi',
            ],
        ];
    }

    private function createParentGroupInMenu($moduleName)
    {
        try {
            // التحقق من عدم وجود مجموعة بنفس الاسم
            $existingGroup = BasicGroup::where('name_ar', $moduleName)
                ->orWhere('name_en', strtolower($moduleName))
                ->first();

            if ($existingGroup) {
                Log::warning('مجموعة أساسية بنفس الاسم موجودة بالفعل: ' . $moduleName);
                $this->dispatchBrowserEvent('warning', [
                    'title' => 'تنبيه!',
                    'message' => 'مجموعة أساسية بنفس الاسم موجودة بالفعل. تم إنشاء الوحدة فقط.'
                ]);
                return false;
            }

            // تحديد ترتيب العرض
            $sortOrder = $this->parentGroupOrder ?: BasicGroup::getSuggestedSortOrder();

            // التحقق من تفرد ترتيب العرض
            $existingSortOrder = BasicGroup::where('sort_order', $sortOrder)->first();
            if ($existingSortOrder) {
                $sortOrder = BasicGroup::getSuggestedSortOrder();
                Log::info('تم تغيير ترتيب العرض إلى: ' . $sortOrder . ' بسبب التضارب');
            }

            // إنشاء المجموعة الأساسية في قاعدة البيانات
            $basicGroup = BasicGroup::create([
                'name_ar' => $this->moduleArName ?: $moduleName, // الاسم العربي أو الإنجليزي كبديل
                'name_en' => $moduleName, // الاسم الإنجليزي كما أدخله المستخدم
                'icon' => $this->parentGroupIcon ?: 'mdi mdi-folder-outline',
                'description_ar' => 'وحدة تم إنشاؤها تلقائياً من مولد الوحدات: ' . ($this->moduleArName ?: $moduleName),
                'description_en' => 'Auto-generated module from module generator: ' . $moduleName,
                'sort_order' => $sortOrder,
                'status' => true,
                'type' => 'item', // تغيير النوع إلى item حسب القاعدة الجديدة
                'route' => $moduleName, // إضافة المسار للوحدة
                'permission' => $moduleName,
                'active_routes' => $moduleName
            ]);

            // إضافة الوحدة للقائمة الديناميكية باستخدام الخدمة المخصصة
            DynamicMenuService::updateMenuForGroup($basicGroup, 'create');

            Log::info('تم إنشاء وحدة رئيسية جديدة: ' . $moduleName . ' (ID: ' . $basicGroup->id . ')');

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم بنجاح!',
                'message' => "تم إنشاء الوحدة الرئيسية '{$moduleName}' وإضافتها للقائمة الجانبية بترتيب {$sortOrder}"
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء المجموعة الأساسية: ' . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ!',
                'message' => 'فشل في إنشاء المجموعة الأساسية: ' . $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * إنشاء المجموعة الأساسية في قاعدة البيانات فقط (بدون إضافة للقائمة الديناميكية)
     */
    private function createParentGroupInDatabase($moduleName)
    {
        try {
            // التحقق من عدم وجود مجموعة بنفس الاسم
            $existingGroup = BasicGroup::where('name_ar', $moduleName)
                ->orWhere('name_en', strtolower($moduleName))
                ->first();

            if ($existingGroup) {
                Log::warning('مجموعة أساسية بنفس الاسم موجودة بالفعل: ' . $moduleName);
                return false;
            }

            // تحديد ترتيب العرض
            $sortOrder = $this->parentGroupOrder ?: BasicGroup::getSuggestedSortOrder();

            // التحقق من تفرد ترتيب العرض
            $existingSortOrder = BasicGroup::where('sort_order', $sortOrder)->first();
            if ($existingSortOrder) {
                $sortOrder = BasicGroup::getSuggestedSortOrder();
                Log::info('تم تغيير ترتيب العرض إلى: ' . $sortOrder . ' بسبب التضارب');
            }

            // إنشاء المجموعة الأساسية في قاعدة البيانات فقط
            $basicGroup = BasicGroup::create([
                'name_ar' => $this->moduleArName ?: $moduleName, // الاسم العربي أو الإنجليزي كبديل
                'name_en' => $moduleName, // الاسم الإنجليزي كما أدخله المستخدم
                'icon' => $this->parentGroupIcon ?: 'mdi mdi-folder-outline',
                'description_ar' => 'مجموعة أساسية تم إنشاؤها تلقائياً لوحدة ' . ($this->moduleArName ?: $moduleName),
                'description_en' => 'Auto-generated parent group for ' . $moduleName . ' module',
                'sort_order' => $sortOrder,
                'status' => true,
                'type' => 'item',
                'route' => $moduleName,
                'permission' => $moduleName,
                'active_routes' => $moduleName
            ]);

            // إضافة الوحدة للقائمة الديناميكية كـ item (وليس group)
            // حتى الوحدات "الأب" يمكن أن توضع تحت مجموعة موجودة
            try {
                $targetParentGroup = null;

                // إذا تم تحديد مجموعة أب صراحة، استخدمها
                if (!empty($this->parentGroup) && $this->parentGroup !== 'standalone') {
                    $targetParentGroup = $this->parentGroup;
                }

                DynamicMenuService::addModuleToMenu(
                    $moduleName,
                    $this->moduleArName ?: $moduleName,
                    $targetParentGroup, // استخدام المجموعة المحددة أو null للوحدات المنفصلة
                    $this->parentGroupIcon ?: 'mdi mdi-folder-outline'
                );

                // التحقق من نجاح إضافة الوحدة للقائمة
                $menuConfig = config('dynamic-menu.menu_items');
                $moduleInMenu = false;
                foreach ($menuConfig as $item) {
                    if (isset($item['permission']) && $item['permission'] === $moduleName) {
                        $moduleInMenu = true;
                        break;
                    }
                }

                if (!$moduleInMenu) {
                    Log::warning("الوحدة {$moduleName} لم تُضف للقائمة الديناميكية، محاولة أخرى...");
                    // محاولة أخرى مع تأخير قصير
                    sleep(1);
                    DynamicMenuService::addModuleToMenu(
                        $moduleName,
                        $this->moduleArName ?: $moduleName,
                        $targetParentGroup,
                        $this->parentGroupIcon ?: 'mdi mdi-folder-outline'
                    );
                } else {
                    Log::info("تم تأكيد إضافة الوحدة {$moduleName} للقائمة الديناميكية");
                }

            } catch (\Exception $menuException) {
                Log::error("خطأ في إضافة الوحدة للقائمة الديناميكية: " . $menuException->getMessage());
                // لا نوقف العملية، فقط نسجل الخطأ
            }

            // إنشاء الصلاحيات المطلوبة للوحدة
            $this->createModulePermissions($moduleName);

            Log::info('تم إنشاء مجموعة أساسية جديدة في قاعدة البيانات والقائمة: ' . $moduleName . ' (ID: ' . $basicGroup->id . ')');

            return true;

        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء المجموعة الأساسية في قاعدة البيانات: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إنشاء الصلاحيات المطلوبة للوحدة الجديدة
     * ملاحظة: تم تعديل هذه الدالة لتجنب تكرار الصلاحيات التي ينشئها أمر Artisan
     */
    private function createModulePermissions($moduleName)
    {
        try {
            // التحقق من وجود صلاحية رئيسية للوحدة - إذا كانت موجودة فهذا يعني أن الأمر الرئيسي أنشأ الصلاحيات بالفعل
            $mainPermissionExists = Permission::where('name', $moduleName)->exists();

            if ($mainPermissionExists) {
                Log::info("الصلاحيات موجودة بالفعل للوحدة: {$moduleName} - تم تخطي إنشاء الصلاحيات المكررة");
                return;
            }

            $lowercaseModuleName = strtolower($moduleName);

            $permissions = [
                $moduleName => "عرض صفحة {$this->moduleArName}",
                "{$lowercaseModuleName}-view" => "عرض {$this->moduleArName}",
                "{$lowercaseModuleName}-list" => "عرض قائمة {$this->moduleArName}",
                "{$lowercaseModuleName}-create" => "إضافة {$this->moduleArName}",
                "{$lowercaseModuleName}-edit" => "تعديل {$this->moduleArName}",
                "{$lowercaseModuleName}-delete" => "حذف {$this->moduleArName}",
            ];

            // إضافة صلاحيات التصدير إذا كانت مفعلة
            if ($this->enableExcelExport) {
                $permissions["{$lowercaseModuleName}-export-excel"] = "تصدير {$this->moduleArName} Excel";
            }

            if ($this->enablePdfExport) {
                $permissions["{$lowercaseModuleName}-export-pdf"] = "تصدير {$this->moduleArName} PDF";
            }

            foreach ($permissions as $name => $description) {
                if (!Permission::where('name', $name)->exists()) {
                    Permission::create([
                        'name' => $name,
                        'guard_name' => 'web',
                        'description' => $description
                    ]);
                }
            }

            Log::info("تم إنشاء صلاحيات الوحدة: {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في إنشاء صلاحيات الوحدة {$moduleName}: " . $e->getMessage());
        }
    }

    /**
     * التحقق من صحة معادلة الحساب
     */
    private function validateCalculationFormula($formula)
    {
        try {
            // إزالة المسافات الزائدة
            $formula = trim($formula);

            if (empty($formula)) {
                return false;
            }

            // قائمة الحقول الرقمية المتاحة (بما في ذلك حقول select الرقمية)
            $availableFields = [];
            foreach ($this->fields as $field) {
                if (in_array($field['type'], ['integer', 'decimal']) ||
                    ($field['type'] === 'select' && isset($field['select_numeric_values']) && $field['select_numeric_values'])) {
                    $availableFields[] = $field['name'];
                }
            }

            // التحقق من وجود أسماء حقول في المعادلة
            $hasFields = false;
            foreach ($availableFields as $fieldName) {
                if (strpos($formula, $fieldName) !== false) {
                    $hasFields = true;
                    break;
                }
            }

            if (!$hasFields) {
                return false; // لا توجد حقول في المعادلة
            }

            // التحقق من الأحرف المسموحة (أحرف، أرقام، مسافات، عمليات حسابية، أقواس، ومقارنات منطقية وشرطية)
            // السماح بـ: أحرف، أرقام، _, مسافات، +، -، *، /، (، )، .، >، <، =، !، &، |، ?، :، %، ^
            if (!preg_match('/^[a-zA-Z0-9_\s\+\-\*\/\(\)\.\>\<\=\!\&\|\?\:\%\^]+$/', $formula)) {
                return false;
            }

            // التحقق من توازن الأقواس
            $openParentheses = substr_count($formula, '(');
            $closeParentheses = substr_count($formula, ')');
            if ($openParentheses !== $closeParentheses) {
                return false;
            }

            // التحقق من عدم وجود عمليات متتالية
            if (preg_match('/[\+\-\*\/]{2,}/', $formula)) {
                return false;
            }

            // التحقق من عدم بدء أو انتهاء المعادلة بعملية (ما عدا السالب في البداية)
            if (preg_match('/^[\+\*\/]|[\+\-\*\/]$/', $formula)) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
