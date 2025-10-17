<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Helpers\DynamicMenuHelper;
use App\Models\System\ModuleField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class GenerateHmvcModule extends Command
{
    protected $signature = 'make:hmvc-module
                            {name : Name of the module (plural, e.g., Products, Users)}
                            {--ar-name= : Arabic name for the module}
                            {--fields= : JSON string of fields configuration}
                            {--fields-file= : Path to JSON file containing fields configuration}
                            {--options= : JSON string of advanced options}
                            {--type=sub : Module type: main or sub (default: sub)}
                            {--parent-group= : Parent group for sub modules (e.g., project, employees)}
                            {--item-icon= : Icon for item modules (e.g., mdi mdi-file-outline)}
                            {--group-order= : Order for group modules (e.g., 5)}';

    protected $description = 'Generate HMVC module with Controller, Livewire Component, Model, Views, and Migration - supports main and sub modules';

    public function handle()
    {
        $name = $this->argument('name');
        $arName = $this->option('ar-name') ?? $name;
        $fieldsJson = $this->option('fields');
        $fieldsFile = $this->option('fields-file');
        $optionsJson = $this->option('options');
        $moduleType = $this->option('type') ?? 'sub';
        $parentGroup = $this->option('parent-group');
        $itemIcon = $this->option('item-icon'); // أيقونة العنصر المخصصة
        $groupOrder = $this->option('group-order'); // ترتيب المجموعة

        // Validate module type
        if (!in_array($moduleType, ['main', 'sub'])) {
            $this->error('Invalid module type. Must be "main" or "sub".');
            return 1;
        }

        // Validate parent group for sub modules
        if ($moduleType === 'sub' && !$parentGroup) {
            $parentGroup = $this->askForParentGroup();
            if (!$parentGroup) {
                $this->error('Parent group is required for sub modules.');
                return 1;
            }
        }

        $fields = [];
        $options = [
            'excel_export' => true,
            'pdf_export' => true,
            'flatpickr' => true,
            'select2' => true,
        ];

        // Parse options if provided
        if ($optionsJson) {
            $parsedOptions = json_decode($optionsJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $options = array_merge($options, $parsedOptions);
            }
        }

        // Load fields from JSON string if provided directly
        if ($fieldsJson) {
            $fields = json_decode($fieldsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in fields option: ' . json_last_error_msg());
                return 1;
            }
            $this->info("✅ تم تحميل " . count($fields) . " حقل من JSON بنجاح");
            foreach ($fields as $field) {
                $arabicName = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
                $this->info("  - {$field['name']} ({$arabicName})");
            }
        }

        // Load fields from file if provided
        if ($fieldsFile && empty($fields)) {
            $fullPath = base_path($fieldsFile);
            if (file_exists($fieldsFile)) {
                $fieldsJsonFromFile = file_get_contents($fieldsFile);
                $jsonData = json_decode($fieldsJsonFromFile, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('Invalid JSON in fields file: ' . json_last_error_msg());
                    return 1;
                }

                // Handle both formats: direct fields array or object with fields property
                if (isset($jsonData['fields']) && is_array($jsonData['fields'])) {
                    $fields = $jsonData['fields'];
                    // Also get module name and arabic name if available
                    $name = $jsonData['module_name'] ?? $name;
                    $arName = $jsonData['arabic_name'] ?? $arName;
                } else if (is_array($jsonData) && !empty($jsonData)) {
                    $fields = $jsonData;
                } else {
                    $this->error('❌ تنسيق ملف JSON غير صحيح. يجب أن يحتوي على fields array');
                    return 1;
                }

                $this->info("✅ تم تحميل " . count($fields) . " حقل من الملف بنجاح");
                foreach ($fields as $field) {
                    $arabicName = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
                    $this->info("  - {$field['name']} ({$arabicName})");
                }
            } else {
                $this->error("❌ ملف الحقول غير موجود: {$fieldsFile}");
                return 1;
            }
        }

        // محاولة جلب الحقول من قاعدة البيانات إذا كانت الوحدة موجودة
        if (empty($fields)) {
            try {
                $existingFields = ModuleField::where('module_name', $name)->get();
                if ($existingFields->isNotEmpty()) {
                    $this->info("✅ تم العثور على " . count($existingFields) . " حقل محفوظ في قاعدة البيانات للوحدة {$name}");

                    $dbFields = [];
                    foreach ($existingFields as $field) {
                        $dbFields[] = [
                            'name' => $field->field_name,
                            'type' => $field->field_type,
                            'ar_name' => $field->arabic_label,
                            'arabic_label' => $field->arabic_label,
                            'required' => $field->is_required ?? true,
                            'searchable' => $field->is_searchable ?? true,
                            'unique' => $field->is_unique ?? false,
                            'size' => $field->field_size,
                            'is_calculated' => $field->is_calculated ?? false,
                            'calculation_type' => $field->calculation_type ?? 'none',
                            'calculation_formula' => $field->calculation_formula,
                            'show_in_table' => $field->show_in_table ?? true,
                            'show_in_forms' => $field->show_in_forms ?? true,
                            'show_in_search' => $field->show_in_search ?? true,
                            // إضافة حقول التاريخ
                            'date_from_field' => $field->date_from_field,
                            'date_to_field' => $field->date_to_field,
                            'date_diff_unit' => $field->date_diff_unit,
                            'remaining_only' => $field->remaining_only ?? false,
                            'is_date_calculated' => $field->is_date_calculated ?? false,
                            // إضافة حقول الوقت
                            'time_from_field' => $field->time_from_field,
                            'time_to_field' => $field->time_to_field,
                            'time_diff_unit' => $field->time_diff_unit,
                            'is_time_calculated' => $field->is_time_calculated ?? false,
                            'absolute_value' => $field->absolute_value ?? false,
                        ];
                    }
                    $fields = $dbFields;

                    // عرض الحقول المحسوبة إذا وجدت
                    $calculatedFields = array_filter($fields, function($field) {
                        return $field['is_calculated'] ?? false;
                    });

                    if (!empty($calculatedFields)) {
                        $this->info("📊 حقول محسوبة موجودة:");
                        foreach ($calculatedFields as $calcField) {
                            $calcType = $calcField['calculation_type'] ?? 'none';
                            if ($calcType === 'date_diff') {
                                $this->info("  📅 {$calcField['name']} ({$calcField['ar_name']}) - فرق التواريخ: {$calcField['date_from_field']} → {$calcField['date_to_field']} ({$calcField['date_diff_unit']})");
                            } elseif ($calcType === 'time_diff') {
                                $this->info("  🕒 {$calcField['name']} ({$calcField['ar_name']}) - فرق الأوقات: {$calcField['time_from_field']} → {$calcField['time_to_field']} ({$calcField['time_diff_unit']})");
                            } elseif ($calcType === 'formula') {
                                $this->info("  🧮 {$calcField['name']} ({$calcField['ar_name']}) - معادلة: {$calcField['calculation_formula']}");
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("تعذر جلب الحقول من قاعدة البيانات: " . $e->getMessage());
            }
        }

        if (empty($fields)) {
            // If no fields provided, prompt user interactively
            $this->warn('⚠️  لا توجد حقول محددة. يجب إدخال الحقول للحصول على أفضل النتائج.');

            if ($this->confirm('هل تريد إدخال الحقول تفاعلياً؟')) {
                $fields = $this->promptForFields();
                if (empty($fields)) {
                    $this->error('❌ لم يتم إدخال أي حقول');
                    return 1;
                }
            } else {
                $this->error('❌ تم إلغاء إنشاء الوحدة. يرجى تقديم ملف JSON للحقول باستخدام --fields-file');
                return 1;
            }
        }

        $this->info("🚀 Generating HMVC Module: {$name} ({$arName})");

        // Create module directory structure
        $this->createDirectories($name);

        // Generate all components
        $this->createController($name, $arName);
        $this->createModel($name, $fields);
        $this->createLivewireComponent($name, $fields, $arName);
        $this->createViews($name, $fields, $arName);
        $this->createModals($name, $fields, $arName);
        $this->createMigration($name, $fields, $arName);
        $this->addRoutes($name, $arName);

        // Auto-run migrations
        $this->info("🔄 Running migrations...");
        try {
            // فحص إذا كانت هناك مايجريشن معلقة قبل التشغيل
            $pendingMigrations = $this->checkPendingMigrations();

            if (!empty($pendingMigrations)) {
                $this->info("📋 Found " . count($pendingMigrations) . " pending migration(s)");
                Artisan::call('migrate', ['--force' => true]);
                $this->info("✅ Migrations executed successfully!");
            } else {
                $this->info("ℹ️ No pending migrations to run");
            }
        } catch (\Exception $e) {
            $this->error("❌ Migration failed: " . $e->getMessage());
            $this->warn("💡 You can run 'php artisan migrate' manually to complete the database setup");
        }

        // Create permissions
        $this->createPermissions($name, $arName, $moduleType, $parentGroup);

        // Create Export class and PDF template
        $this->createExportClass($name, $fields, $arName);
        $this->createPdfTemplate($name, $fields, $arName);
        $this->createPrintTemplate($name, $fields, $arName);
        $this->createTcpdfController($name, $fields, $arName);
        $this->createPrintController($name, $fields, $arName);

        // Automatically add to navigation based on module type
        $this->addToNavigation($name, $arName, $moduleType, $parentGroup);

        // Show navigation integration code
        $this->showNavigationCode($name, $arName);

        // Save module fields configuration for future editing
        $this->saveModuleFieldsConfiguration($name, $fields, $arName);

        $this->info("🎉 Module {$name} created successfully!");
        $this->info("📊 Module Type: " . ($moduleType === 'main' ? 'رئيسية' : 'فرعية'));
        if ($moduleType === 'sub') {
            $this->info("📂 Parent Group: {$parentGroup}");
        }
        return 0;
    }

    /**
     * Ask user to select parent group for sub modules
     */
    protected function askForParentGroup()
    {
        $this->info("🔍 اختيار المجموعة الأب للوحدة الفرعية:");

        // Get existing groups from dynamic menu
        $menuItems = config('dynamic-menu.menu_items', []);
        $existingGroups = [];

        foreach ($menuItems as $item) {
            if ($item['type'] === 'group') {
                $existingGroups[$item['permission']] = "{$item['title']} ({$item['permission']})";
            }
        }

        // Add option to create new group
        $existingGroups['new'] = 'إنشاء مجموعة جديدة';

        if (empty($existingGroups)) {
            return $this->askForNewGroup();
        }

        $choice = $this->choice('اختر المجموعة الأب:', $existingGroups);

        if ($choice === 'إنشاء مجموعة جديدة') {
            return $this->askForNewGroup();
        }

        // Find the permission key for selected group
        foreach ($existingGroups as $permission => $display) {
            if ($display === $choice) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * Ask user to create a new group
     */
    protected function askForNewGroup()
    {
        $groupPermission = $this->ask('اسم الصلاحية للمجموعة الجديدة (مثال: reports)');
        if (!$groupPermission) {
            return null;
        }

        $groupTitle = $this->ask('العنوان العربي للمجموعة الجديدة (مثال: التقارير)');
        if (!$groupTitle) {
            $groupTitle = $groupPermission;
        }

        $groupIcon = $this->ask('أيقونة المجموعة (مثال: mdi mdi-chart-line)', 'mdi mdi-folder-outline');

        // Create the new group
        DynamicMenuHelper::addMenuGroup($groupPermission, $groupTitle, $groupIcon);

        $this->info("✅ تم إنشاء مجموعة جديدة: {$groupTitle}");

        return $groupPermission;
    }

    protected function promptForFields()
    {
        $fields = [];
        $this->info("📝 إدخال الحقول (اتركه فارغ للانتهاء):");

        while (true) {
            $fieldName = $this->ask('اسم الحقل (مثال: user_name)');

            if (empty($fieldName)) {
                break;
            }

            $arabicLabel = $this->ask("التسمية العربية للحقل {$fieldName}");

            // Display Arabic options but return English keys
            $fieldTypeOptions = [
                'نص' => 'string',
                'بريد إلكتروني' => 'email',
                'رقم صحيح' => 'integer',
                'تاريخ' => 'date',
                'تاريخ ووقت' => 'datetime',
                'وقت فقط' => 'time',
                'شهر / سنة' => 'month_year',
                'نص طويل' => 'text',
                'رقم عشري' => 'decimal'
            ];

            $selectedType = $this->choice('نوع الحقل', array_keys($fieldTypeOptions), 'نص');
            $fieldType = $fieldTypeOptions[$selectedType];

            $required = $this->confirm("هل الحقل {$fieldName} مطلوب؟", true);
            $searchable = $this->confirm("هل الحقل {$fieldName} قابل للبحث؟", true);

            // Ask for additional field options
            $unique = false;
            $size = null;
            $arabicOnly = false;
            $numericOnly = false;

            if ($fieldType === 'string') {
                $unique = $this->confirm("هل الحقل {$fieldName} يجب أن يكون فريداً (unique)؟", false);
                $size = $this->ask("حجم الحقل (اتركه فارغاً لـ 255)", '');
                if (empty($size)) $size = null;
                else $size = intval($size);

                // Ask for validation type (mutually exclusive)
                $validationTypeOptions = [
                    'لا يوجد' => 'none',
                    'عربي فقط' => 'arabic_only',
                    'أرقام فقط' => 'numeric_only'
                ];

                $selectedValidationType = $this->choice("نوع التحقق من البيانات للحقل {$fieldName}", array_keys($validationTypeOptions), 'لا يوجد');
                $validationType = $validationTypeOptions[$selectedValidationType];

                if ($validationType === 'arabic_only') {
                    $arabicOnly = true;
                } elseif ($validationType === 'numeric_only') {
                    $numericOnly = true;
                }
            } elseif ($fieldType === 'text') {
                $size = $this->ask("حجم الحقل (اتركه فارغاً للنص الطويل الافتراضي)", '');
                if (empty($size)) $size = null;
                else $size = intval($size);
            } elseif ($fieldType === 'integer') {
                $numericOnly = true; // تلقائياً للأرقام
            }

            $fieldData = [
                'name' => $fieldName,
                'type' => $fieldType,
                'ar_name' => $arabicLabel,
                'arabic_label' => $arabicLabel, // للتوافق مع النسختين
                'required' => $required,
                'searchable' => $searchable
            ];

            // Add optional properties if set
            if ($unique) $fieldData['unique'] = true;
            if ($size !== null) $fieldData['size'] = $size;
            if ($arabicOnly) $fieldData['arabic_only'] = true;
            if ($numericOnly) $fieldData['numeric_only'] = true;

            // سؤال عن قواعد التحقق الإضافية
            if ($this->confirm("هل تريد إضافة قواعد تحقق إضافية للحقل {$fieldName}؟", false)) {
                $additionalRules = $this->ask('قواعد التحقق الإضافية (مثال: min:3,max:50) أو اتركه فارغاً');
                if (!empty($additionalRules)) {
                    $fieldData['validation_rules'] = $additionalRules;
                }
            }

            // سؤال عن رسائل التحقق المخصصة
            if ($this->confirm("هل تريد إضافة رسائل تحقق مخصصة للحقل {$fieldName}؟", false)) {
                $customMessages = [];

                $requiredMsg = $this->ask('رسالة الحقل المطلوب (required) أو اتركه فارغاً');
                if (!empty($requiredMsg)) $customMessages['required'] = $requiredMsg;

                $maxMsg = $this->ask('رسالة الحد الأقصى (max) أو اتركه فارغاً');
                if (!empty($maxMsg)) $customMessages['max'] = $maxMsg;

                $uniqueMsg = $this->ask('رسالة القيمة المكررة (unique) أو اتركه فارغاً');
                if (!empty($uniqueMsg)) $customMessages['unique'] = $uniqueMsg;

                if (!empty($customMessages)) {
                    $fieldData['validation_messages'] = $customMessages;
                }
            }

            // سؤال عن الخصائص المخصصة
            if ($this->confirm("هل تريد إضافة خصائص HTML مخصصة للحقل {$fieldName}؟", false)) {
                $customAttrs = [];

                $placeholder = $this->ask('النص التوضيحي (placeholder) أو اتركه فارغاً');
                if (!empty($placeholder)) {
                    $customAttrs['placeholder'] = $placeholder;
                }

                $cssClass = $this->ask('الكلاسات CSS الإضافية أو اتركه فارغاً');
                if (!empty($cssClass)) {
                    $customAttrs['class'] = $cssClass;
                }

                if (!empty($customAttrs)) {
                    $fieldData['custom_attributes'] = $customAttrs;
                }
            }

            // سؤال عن العمليات الحسابية العادية (للحقول الرقمية)
            if (in_array($fieldType, ['integer', 'decimal']) && $this->confirm("هل تريد أن يكون هذا الحقل محسوباً رياضياً؟", false)) {
                $this->info("🧮 إعداد حساب رياضي للحقل {$fieldName}:");

                $formula = $this->ask('أدخل المعادلة الحسابية (مثال: field1 + field2 * 10)');

                if (!empty($formula)) {
                    $fieldData['calculation_type'] = 'formula';
                    $fieldData['calculation_formula'] = $formula;
                    $fieldData['is_calculated'] = true;

                    $this->info("✅ تم إعداد المعادلة الحسابية: {$formula}");
                }
            }

            // سؤال عن حسابات التاريخ
            if ($this->confirm("هل تريد أن يكون هذا الحقل محسوباً من تاريخين؟", false)) {
                $this->info("📊 إعداد حساب التاريخ للحقل {$fieldName}:");

                // اختيار حقلي التاريخ
                $dateFromField = $this->ask('اسم حقل التاريخ الأول (من)');
                $dateToField = $this->ask('اسم حقل التاريخ الثاني (إلى)');

                // اختيار وحدة الحساب
                $unitOptions = [
                    'أيام' => 'days',
                    'أشهر' => 'months',
                    'سنوات' => 'years'
                ];

                $selectedUnit = $this->choice('وحدة الحساب', array_keys($unitOptions), 'أيام');
                $unit = $unitOptions[$selectedUnit];

                // الباقي فقط (للأيام والأشهر)
                $remainingOnly = false;
                if (in_array($unit, ['days', 'months'])) {
                    $remainingOnly = $this->confirm('هل تريد عرض الباقي فقط (بدون الوحدات الأكبر)؟', true);
                }

                // حفظ إعدادات حساب التاريخ
                $fieldData['calculation_type'] = 'date_diff';
                $fieldData['date_from_field'] = $dateFromField;
                $fieldData['date_to_field'] = $dateToField;
                $fieldData['date_diff_unit'] = $unit;
                $fieldData['remaining_only'] = $remainingOnly;
                $fieldData['is_calculated'] = true;
                $fieldData['is_date_calculated'] = true;

                $this->info("✅ تم إعداد حساب التاريخ: {$dateFromField} إلى {$dateToField} بوحدة {$selectedUnit}");
            }

            // سؤال عن حسابات الوقت
            if ($fieldType === 'integer' && $this->confirm("هل تريد أن يكون هذا الحقل محسوباً من وقتين؟", false)) {
                $this->info("🕒 إعداد حساب الوقت للحقل {$fieldName}:");

                // اختيار حقلي الوقت
                $timeFromField = $this->ask('اسم حقل الوقت الأول (من)');
                $timeToField = $this->ask('اسم حقل الوقت الثاني (إلى)');

                // اختيار وحدة الحساب
                $timeUnitOptions = [
                    'دقائق' => 'minutes',
                    'ساعات' => 'hours'
                ];

                $selectedTimeUnit = $this->choice('وحدة الحساب', array_keys($timeUnitOptions), 'دقائق');
                $timeUnit = $timeUnitOptions[$selectedTimeUnit];

                // قيمة مطلقة
                $absoluteValue = $this->confirm('هل تريد قيمة مطلقة (موجبة دائماً)؟', false);

                // حفظ إعدادات حساب الوقت
                $fieldData['calculation_type'] = 'time_diff';
                $fieldData['time_from_field'] = $timeFromField;
                $fieldData['time_to_field'] = $timeToField;
                $fieldData['time_diff_unit'] = $timeUnit;
                $fieldData['absolute_value'] = $absoluteValue;
                $fieldData['is_calculated'] = true;
                $fieldData['is_time_calculated'] = true;

                $this->info("✅ تم إعداد حساب الوقت: {$timeFromField} إلى {$timeToField} بوحدة {$selectedTimeUnit}");
            }

            $fields[] = $fieldData;

            $this->info("✅ تم إضافة الحقل: {$fieldName} ({$arabicLabel})");
        }

        if (empty($fields)) {
            $this->error('❌ يجب إدخال حقل واحد على الأقل');
            return null;
        }

        return $fields;
    }

    protected function createDirectories($name)
    {
        // متغيرات kebab-case للمجلدات
        $kebabName = Str::kebab($name);

        $directories = [
            base_path("app/Http/Controllers/{$name}"),
            base_path("app/Http/Livewire/{$name}"),
            base_path("app/Models/{$name}"),
            base_path("resources/views/livewire/{$kebabName}"),
            base_path("resources/views/livewire/{$kebabName}/modals"),
            base_path("resources/views/content/{$name}"),
        ];

        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("📁 Created directory: {$dir}");
            }
        }
    }

    protected function createController($name, $arName)
    {
        $singularName = Str::singular($name);

        $content = "<?php
namespace App\\Http\\Controllers\\{$name};
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
class {$singularName}Controller extends Controller
{
    public function index()
    {
        return view('content.{$name}.index');
    }
}";

        $path = base_path("app/Http/Controllers/{$name}/{$singularName}Controller.php");
        File::put($path, $content);
        $this->info("🎮 Created Controller");
    }
    protected function createModel($name, $fields)
    {
        $singularName = Str::singular($name);
        $tableName = Str::snake(Str::plural($name));

        // Generate relationships for select fields that reference other tables
        $relationships = "\n     public function user()\n    {\n        return \$this->belongsTo(User::class, 'user_id', 'id');\n    }";

        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (($field['type'] ?? '') === 'select' &&
                    ($field['select_source'] ?? '') === 'database' &&
                    !empty($field['related_table'] ?? $field['select_table'] ?? '')
                ) {

                    $relatedTable = $field['related_table'] ?? $field['select_table'] ?? '';
                    $relatedKey = $field['related_key'] ?? $field['select_value'] ?? 'id';
                    $relatedDisplay = $field['related_display'] ?? $field['select_label'] ?? '';

                    // Determine the correct field name for different tables
                    if (empty($relatedDisplay)) {
                        if ($relatedTable === 'departments') {
                            $relatedDisplay = 'department_name';
                        } else {
                            $relatedDisplay = 'name';
                        }
                    }

                    $relationshipName = Str::singular($relatedTable);
                    $modelName = Str::studly(Str::singular($relatedTable));
                    $pluralModelName = Str::studly($relatedTable);

                    $relationships .= "\n\n    // علاقة مع {$relatedTable}\n";
                    $relationships .= "    public function {$relationshipName}()\n    {\n";
                    $relationships .= "        // محاولة الحصول على النموذج الصحيح\n";
                    $relationships .= "        if (class_exists('App\\Models\\{$pluralModelName}\\{$pluralModelName}')) {\n";
                    $relationships .= "            return \$this->belongsTo('App\\Models\\{$pluralModelName}\\{$pluralModelName}', '{$field['name']}', '{$relatedKey}');\n";
                    $relationships .= "        } elseif (class_exists('App\\Models\\{$modelName}\\{$modelName}')) {\n";
                    $relationships .= "            return \$this->belongsTo('App\\Models\\{$modelName}\\{$modelName}', '{$field['name']}', '{$relatedKey}');\n";
                    $relationships .= "        }\n        \n        return null;\n";
                    $relationships .= "    }\n\n";

                    // Add helper method to get the display name
                    $fieldArName = $field['ar_name'] ?? $field['name'];
                    $helperMethodName = Str::camel($field['name'] . '_name');
                    $relationships .= "    // Helper method لجلب اسم {$fieldArName}\n";
                    $relationships .= "    public function get" . Str::studly($helperMethodName) . "Attribute()\n    {\n";
                    $relationships .= "        if (\$this->{$relationshipName}) {\n";
                    $relationships .= "            return \$this->{$relationshipName}->{$relatedDisplay} ?? \$this->{$relationshipName}->name ?? 'غير محدد';\n";
                    $relationships .= "        }\n        return 'غير محدد';\n";
                    $relationships .= "    }";
                }
            }
        }

        $content = "<?php
namespace App\\Models\\{$name};
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use App\\Models\\User;

class {$name} extends Model
{
     use HasFactory;
    protected \$guarded = [];
    protected \$table = \"{$tableName}\";
{$relationships}
}";

        $path = base_path("app/Models/{$name}/{$name}.php");
        File::put($path, $content);
        $this->info("🏗️ Created Model");
    }

    protected function createLivewireComponent($name, $fields, $arName)
    {
        $singularName = Str::singular($name);
        $lowerSingular = strtolower($singularName);
        $lowerName = strtolower($name);
        $tableName = Str::snake(Str::plural($name)); // Table name for database operations

        // متغيرات جديدة لـ Livewire kebab-case
        $kebabName = Str::kebab($name);
        $kebabSingular = Str::kebab($singularName);

        // ===== القواعد الأساسية (للوحدات بدون حقول مخصصة) =====
        $validationRulesBasic = "'{$lowerSingular}_name' => 'required|unique:{$tableName},{$lowerSingular}_name'";
        $updateValidationRulesBasic = "'{$lowerSingular}_name' => 'required|unique:{$tableName},{$lowerSingular}_name,' . (\$this->{$lowerSingular}->id ?? null) . ',id'";
        $validationMessagesBasic = "'{$lowerSingular}_name.required' => 'يرجى إدخال الاسم',\n            '{$lowerSingular}_name.unique' => 'هذا الاسم موجود بالفعل'";

        // Generate properties
        if (empty($fields)) {
            $properties = "public \${$lowerSingular}_name;";
            $searchArray = "['{$lowerSingular}_name' => '']";
            $searchFields = "'{$lowerSingular}_name'";
            $searchFilter = "\${$lowerSingular}_nameSearch = '%' . \$this->search['{$lowerSingular}_name'] . '%';";
            $queryCondition = "->when(\$this->search['{$lowerSingular}_name'], function (\$query) use (\${$lowerSingular}_nameSearch) {\n                \$query->where('{$lowerSingular}_name', 'LIKE', \${$lowerSingular}_nameSearch);\n            })";
            $createFields = "'user_id' => Auth::user()->id,\n            '{$lowerSingular}_name' => \$this->{$lowerSingular}_name";
            $updateFields = "'user_id' => Auth::user()->id,\n            '{$lowerSingular}_name' => \$this->{$lowerSingular}_name";
            $fieldAssignments = "\$this->{$lowerSingular}_name = \$this->{$lowerSingular}->{$lowerSingular}_name;";
            $detailsText = "\"اسم ال{$arName}: \" . \$this->{$lowerSingular}_name";
            $deleteDetailsText = "\"اسم ال{$arName}: \" . \${$singularName}->{$lowerSingular}_name";
        } else {
            // For complex fields
            $propArray = [];
            $searchPropArray = [];
            $searchFieldsArray = [];
            $validationRulesArray = [];
            $updateValidationRulesArray = []; // Separate validation for update
            $validationMessagesArray = [];
            $updateValidationMessagesArray = []; // Separate messages for update
            $createFieldsArray = [];
            $updateFieldsArray = [];
            $assignmentsArray = [];

            foreach ($fields as $field) {
                // Initialize properties with correct default values
                if (($field['type'] ?? 'string') === 'checkbox' || ($field['type'] ?? 'string') === 'boolean') {
                    $propArray[] = "public \${$field['name']} = false; // Initialize as false for checkbox";
                } else {
                    $propArray[] = "public \${$field['name']};";
                }

                // Add preview variable for file fields
                if (($field['type'] ?? 'string') === 'file') {
                    $propArray[] = "public \$previewFile{$field['name']};";
                }

                if ($field['searchable'] ?? true) {
                    $searchPropArray[] = "'{$field['name']}' => ''";
                    $searchFieldsArray[] = "'{$field['name']}'";
                }

                if ($field['required'] ?? true) {
                    $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];

                    if (($field['type'] ?? 'string') === 'file') {
                        // For create - file is required
                        $validationRulesArray[] = "'{$field['name']}' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240'";
                        $validationMessagesArray[] = "'{$field['name']}.required' => 'يرجى اختيار {$arabicLabel}'";
                        $validationMessagesArray[] = "'{$field['name']}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                        $validationMessagesArray[] = "'{$field['name']}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                        $validationMessagesArray[] = "'{$field['name']}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";

                        // For update - file is optional (nullable)
                        $updateValidationRulesArray[] = "'{$field['name']}' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";
                    } elseif (($field['type'] ?? 'string') === 'checkbox' || ($field['type'] ?? 'string') === 'boolean') {
                        // For checkbox/boolean fields - no validation needed usually, but can add if required
                        // $validationRulesArray[] = "'{$field['name']}' => 'boolean'";
                        // $updateValidationRulesArray[] = "'{$field['name']}' => 'boolean'";
                    } else {
                        // Handle unique field validation for required fields
                        if (isset($field['unique']) && $field['unique']) {
                            // Build validation rules
                            $rules = ['required', "unique:{$tableName},{$field['name']}"];
                            $updateRules = ['required', "unique:{$tableName},{$field['name']},' . (\$this->{$lowerSingular}->id ?? null) . ',id"];

                            // Add max length if specified
                            if (isset($field['size']) && is_numeric($field['size']) && in_array($field['type'] ?? 'string', ['string', 'varchar', 'text'])) {
                                $rules[] = "max:{$field['size']}";
                                $updateRules[] = "max:{$field['size']}";
                            }

                            // Add numeric validation based on field type
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $rules[] = 'numeric';
                                $updateRules[] = 'numeric';
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $rules[] = 'integer';
                                $updateRules[] = 'integer';
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // For decimal fields, use regex only to avoid conversion issues
                                $rules[] = 'regex:/^\d+(\.\d{1,2})?$/';
                                $updateRules[] = 'regex:/^\d+(\.\d{1,2})?$/';
                            }

                            // Add arabic only validation if specified (old method for compatibility)
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                                $updateRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                            }

                            // Add text content type validation (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                                        $updateRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                                        break;
                                    case 'english_only':
                                        $rules[] = 'regex:/^[a-zA-Z\s]+$/';
                                        $updateRules[] = 'regex:/^[a-zA-Z\s]+$/';
                                        break;
                                    case 'numeric_only':
                                        $rules[] = 'regex:/^[0-9]+$/';
                                        $updateRules[] = 'regex:/^[0-9]+$/';
                                        break;
                                    case 'any':
                                    default:
                                        // لا نضيف قواعد إضافية
                                        break;
                                }
                            }

                            // For create: unique field
                            $validationRulesArray[] = "'{$field['name']}' => '" . implode('|', $rules) . "'";
                            $validationMessagesArray[] = "'{$field['name']}.required' => 'يرجى إدخال {$arabicLabel}'";
                            $validationMessagesArray[] = "'{$field['name']}.unique' => '{$arabicLabel} موجود بالفعل'";
                            if (isset($field['size'])) {
                                $validationMessagesArray[] = "'{$field['name']}.max' => '{$arabicLabel} يجب أن يكون أقل من {$field['size']} حرف'";
                            }
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $validationMessagesArray[] = "'{$field['name']}.numeric' => '{$arabicLabel} يجب أن يكون رقم فقط'";
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $validationMessagesArray[] = "'{$field['name']}.integer' => '{$arabicLabel} يجب أن يكون رقم صحيح'";
                                // Add detailed integer validation messages based on integer type
                                $integerMessages = $this->getIntegerValidationMessages($field);
                                foreach ($integerMessages as $key => $message) {
                                    $validationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // Add detailed decimal validation messages based on precision and scale
                                $decimalMessages = $this->getDecimalValidationMessages($field);
                                foreach ($decimalMessages as $key => $message) {
                                    $validationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            }
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                            }

                            // Add text content type validation messages (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                                        break;
                                    case 'english_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف إنجليزية فقط'";
                                        break;
                                    case 'numeric_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أرقام فقط'";
                                        break;
                                }
                            }

                            // For update: unique field except current record
                            $updateValidationRulesArray[] = "'{$field['name']}' => '" . implode('|', $updateRules) . "'";
                            $updateValidationMessagesArray[] = "'{$field['name']}.required' => 'يرجى إدخال {$arabicLabel}'";
                            $updateValidationMessagesArray[] = "'{$field['name']}.unique' => '{$arabicLabel} موجود بالفعل'";
                            if (isset($field['size'])) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.max' => '{$arabicLabel} يجب أن يكون أقل من {$field['size']} حرف'";
                            }
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.numeric' => '{$arabicLabel} يجب أن يكون رقم فقط'";
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $updateValidationMessagesArray[] = "'{$field['name']}.integer' => '{$arabicLabel} يجب أن يكون رقم صحيح'";
                                // Add detailed integer validation messages for update
                                $integerMessages = $this->getIntegerValidationMessages($field);
                                foreach ($integerMessages as $key => $message) {
                                    $updateValidationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // Add detailed decimal validation messages for update
                                $decimalMessages = $this->getDecimalValidationMessages($field);
                                foreach ($decimalMessages as $key => $message) {
                                    $updateValidationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            }
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                            }

                            // Add text content type validation messages for update (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                                        break;
                                    case 'english_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف إنجليزية فقط'";
                                        break;
                                    case 'numeric_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أرقام فقط'";
                                        break;
                                }
                            }
                        } else {
                            // Build validation rules for normal required fields
                            $rules = ['required'];

                            // Add max length if specified
                            if (isset($field['size']) && is_numeric($field['size']) && in_array($field['type'] ?? 'string', ['string', 'varchar', 'text'])) {
                                $rules[] = "max:{$field['size']}";
                            }

                            // Add numeric validation based on field type
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $rules[] = 'numeric';
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $rules[] = 'integer';
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // For decimal fields, use regex only to avoid conversion issues
                                $rules[] = 'regex:/^\d+(\.\d{1,2})?$/';
                            }

                            // Add arabic only validation if specified (old method for compatibility)
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                            }

                            // Add text content type validation (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                                        break;
                                    case 'english_only':
                                        $rules[] = 'regex:/^[a-zA-Z\s]+$/';
                                        break;
                                    case 'numeric_only':
                                        $rules[] = 'regex:/^[0-9]+$/';
                                        break;
                                    case 'any':
                                    default:
                                        // لا نضيف قواعد إضافية
                                        break;
                                }
                            }

                            // Normal required field validation
                            $validationRulesArray[] = "'{$field['name']}' => '" . implode('|', $rules) . "'";
                            $validationMessagesArray[] = "'{$field['name']}.required' => 'يرجى إدخال {$arabicLabel}'";
                            if (isset($field['size'])) {
                                $validationMessagesArray[] = "'{$field['name']}.max' => '{$arabicLabel} يجب أن يكون أقل من {$field['size']} حرف'";
                            }
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $validationMessagesArray[] = "'{$field['name']}.numeric' => '{$arabicLabel} يجب أن يكون رقم فقط'";
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $validationMessagesArray[] = "'{$field['name']}.integer' => '{$arabicLabel} يجب أن يكون رقم صحيح'";
                                // Add detailed integer validation messages for normal fields
                                $integerMessages = $this->getIntegerValidationMessages($field);
                                foreach ($integerMessages as $key => $message) {
                                    $validationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // Add detailed decimal validation messages for normal fields
                                $decimalMessages = $this->getDecimalValidationMessages($field);
                                foreach ($decimalMessages as $key => $message) {
                                    $validationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            }
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                            }

                            // Add text content type validation messages for normal fields (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                                        break;
                                    case 'english_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف إنجليزية فقط'";
                                        break;
                                    case 'numeric_only':
                                        $validationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أرقام فقط'";
                                        break;
                                }
                            }

                            // For update - same rules as create for non-file fields
                            $updateValidationRulesArray[] = "'{$field['name']}' => '" . implode('|', $rules) . "'";
                            $updateValidationMessagesArray[] = "'{$field['name']}.required' => 'يرجى إدخال {$arabicLabel}'";
                            if (isset($field['size'])) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.max' => '{$arabicLabel} يجب أن يكون أقل من {$field['size']} حرف'";
                            }
                            if (isset($field['numeric_only']) && $field['numeric_only']) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.numeric' => '{$arabicLabel} يجب أن يكون رقم فقط'";
                            } elseif (($field['type'] ?? 'string') === 'integer') {
                                $updateValidationMessagesArray[] = "'{$field['name']}.integer' => '{$arabicLabel} يجب أن يكون رقم صحيح'";
                                // Add detailed integer validation messages for update normal fields
                                $integerMessages = $this->getIntegerValidationMessages($field);
                                foreach ($integerMessages as $key => $message) {
                                    $updateValidationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            } elseif (($field['type'] ?? 'string') === 'decimal') {
                                // Add detailed decimal validation messages for update normal fields
                                $decimalMessages = $this->getDecimalValidationMessages($field);
                                foreach ($decimalMessages as $key => $message) {
                                    $updateValidationMessagesArray[] = "'{$key}' => '{$message}'";
                                }
                            }
                            if (isset($field['arabic_only']) && $field['arabic_only']) {
                                $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                            }

                            // Add text content type validation messages for update normal fields (new method)
                            if (isset($field['text_content_type'])) {
                                switch ($field['text_content_type']) {
                                    case 'arabic_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                                        break;
                                    case 'english_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف إنجليزية فقط'";
                                        break;
                                    case 'numeric_only':
                                        $updateValidationMessagesArray[] = "'{$field['name']}.regex' => '{$arabicLabel} يجب أن يحتوي على أرقام فقط'";
                                        break;
                                }
                            }
                        }
                    }
                } else {
                    // Handle non-required (nullable) fields
                    $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];

                    if (($field['type'] ?? 'string') === 'file') {
                        // File fields - always nullable for non-required
                        $validationRulesArray[] = "'{$field['name']}' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'";
                        $validationMessagesArray[] = "'{$field['name']}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                        $validationMessagesArray[] = "'{$field['name']}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                        $validationMessagesArray[] = "'{$field['name']}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";

                        // For update - same rules
                        $updateValidationRulesArray[] = "'{$field['name']}' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";
                    } elseif (isset($field['unique']) && $field['unique']) {
                        // Handle unique field validation for nullable fields
                        // For create: nullable unique field
                        $validationRulesArray[] = "'{$field['name']}' => 'nullable|unique:{$tableName},{$field['name']}'";
                        $validationMessagesArray[] = "'{$field['name']}.unique' => '{$arabicLabel} موجود بالفعل'";

                        // For update: nullable unique field except current record
                        $updateValidationRulesArray[] = "'{$field['name']}' => 'nullable|unique:{$tableName},{$field['name']},' . (\$this->{$lowerSingular}->id ?? null) . ',id'";
                        $updateValidationMessagesArray[] = "'{$field['name']}.unique' => '{$arabicLabel} موجود بالفعل'";
                    }
                    // Note: No validation rules needed for regular nullable fields
                }

                if (($field['type'] ?? 'string') === 'file') {
                    // Handle file fields with null check for optional files
                    if ($field['required'] ?? true) {
                        // For required files, direct access (validation ensures file exists)
                        $createFieldsArray[] = "'{$field['name']}' => \$this->{$field['name']}->hashName()";
                    } else {
                        // For optional files, check if file exists before accessing hashName()
                        $createFieldsArray[] = "'{$field['name']}' => \$this->{$field['name']} ? \$this->{$field['name']}->hashName() : null";
                    }
                } elseif (($field['type'] ?? 'string') === 'checkbox' || ($field['type'] ?? 'string') === 'boolean') {
                    $createFieldsArray[] = "'{$field['name']}' => (bool)\$this->{$field['name']}";
                } else {
                    $createFieldsArray[] = "'{$field['name']}' => \$this->{$field['name']}";
                }
                if (($field['type'] ?? 'string') === 'file') {
                    $updateFieldsArray[] = "// '{$field['name']}' will be handled separately if updated";
                } elseif (($field['type'] ?? 'string') === 'checkbox' || ($field['type'] ?? 'string') === 'boolean') {
                    $updateFieldsArray[] = "'{$field['name']}' => (bool)\$this->{$field['name']}";
                } else {
                    $updateFieldsArray[] = "'{$field['name']}' => \$this->{$field['name']}";
                }

                // Handle file field assignments differently
                if (($field['type'] ?? 'string') === 'file') {
                    $assignmentsArray[] = "\$this->previewFile{$field['name']} = \$this->{$lowerSingular}->{$field['name']}; // For preview";
                    $assignmentsArray[] = "\$this->{$field['name']} = null; // Reset file input for new upload";
                } elseif (($field['type'] ?? 'string') === 'checkbox' || ($field['type'] ?? 'string') === 'boolean') {
                    $assignmentsArray[] = "\$this->{$field['name']} = (bool)\$this->{$lowerSingular}->{$field['name']}; // Convert to boolean for checkbox";
                } else {
                    $assignmentsArray[] = "\$this->{$field['name']} = \$this->{$lowerSingular}->{$field['name']};";
                }
            }

            $properties = implode("\n    ", $propArray);
            $searchArray = '[' . implode(', ', $searchPropArray) . ']';
            $searchFields = implode(', ', $searchFieldsArray);

            // Generate search filters
            $searchFilterArray = [];
            $queryConditionArray = [];
            foreach ($fields as $field) {
                if ($field['searchable'] ?? true) {
                    $searchVar = $field['name'] . 'Search';
                    $fieldType = $field['type'] ?? 'text';

                    if ($fieldType === 'checkbox' || $fieldType === 'boolean') {
                        // For boolean fields, exact match search
                        $searchFilterArray[] = "\${$searchVar} = \$this->search['{$field['name']}'];";
                        $queryConditionArray[] = "->when(\$this->search['{$field['name']}'] !== '' && \$this->search['{$field['name']}'] !== null, function (\$query) use (\${$searchVar}) {\n                \$query->where('{$field['name']}', (bool)\${$searchVar});\n            })";
                    } elseif ($fieldType === 'select') {
                        // For select fields, exact match search
                        $searchFilterArray[] = "\${$searchVar} = \$this->search['{$field['name']}'];";
                        $queryConditionArray[] = "->when(\$this->search['{$field['name']}'], function (\$query) use (\${$searchVar}) {\n                \$query->where('{$field['name']}', \${$searchVar});\n            })";
                    } else {
                        // For text, date, number fields, LIKE search
                        $searchFilterArray[] = "\${$searchVar} = '%' . \$this->search['{$field['name']}'] . '%';";
                        $queryConditionArray[] = "->when(\$this->search['{$field['name']}'], function (\$query) use (\${$searchVar}) {\n                \$query->where('{$field['name']}', 'LIKE', \${$searchVar});\n            })";
                    }
                }
            }
            $searchFilter = implode("\n        ", $searchFilterArray);
            $queryCondition = implode("\n            ", $queryConditionArray);

            // ===== تحويل المصفوفات إلى نصوص للاستخدام في القالب =====
            $validationRules = implode(",\n            ", $validationRulesArray);
            $updateValidationRules = implode(",\n            ", $updateValidationRulesArray);
            $validationMessages = implode(",\n            ", $validationMessagesArray);
            $updateValidationMessages = implode(",\n            ", $updateValidationMessagesArray);

            // ===== القواعد الاحتياطية (في حالة عدم وجود حقول محددة) =====
            $fallbackStoreRules = !empty($validationRulesArray) ? $validationRules : $validationRulesBasic;
            $fallbackUpdateRules = !empty($updateValidationRulesArray) ? $updateValidationRules : $updateValidationRulesBasic;
            $fallbackStoreMessages = !empty($validationMessagesArray) ? $validationMessages : $validationMessagesBasic;
            $createFields = "'user_id' => Auth::user()->id,\n            " . implode(",\n            ", $createFieldsArray);
            $updateFields = "'user_id' => Auth::user()->id,\n            " . implode(",\n            ", $updateFieldsArray);
            $fieldAssignments = implode("\n        ", $assignmentsArray);
            $detailsText = "\"تم اضافة {$arName} جديد\"";
            $deleteDetailsText = "\"تم حذف {$arName}\"";

            // Generate file upload code for create
            $fileUploadCode = '';
            $updateFileUploadCode = '';
            if (!empty($fields)) {
                $fileFields = array_filter($fields, function ($field) {
                    return ($field['type'] ?? '') === 'file';
                });

                if (!empty($fileFields)) {
                    $uploadStatements = [];
                    $updateUploadStatements = [];
                    foreach ($fileFields as $field) {
                        $uploadStatements[] = "if (\$this->{$field['name']}) {
            \$this->{$field['name']}->store('public/{$lowerName}');
            \$fileData['{$field['name']}'] = \$this->{$field['name']}->hashName();
        }";

                        $updateUploadStatements[] = "// Handle file upload if new file is provided
        if (\$this->{$field['name']}) {
            \$this->{$field['name']}->store('public/{$lowerName}');
            \$updateData['{$field['name']}'] = \$this->{$field['name']}->hashName();
        }";
                    }
                    $fileUploadCode = implode("\n        ", $uploadStatements);
                    $updateFileUploadCode = implode("\n        ", $updateUploadStatements);
                }
            }
        }

        // Check if we need file upload support
        $hasFileFields = false;
        $relatedModels = [];

        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (($field['type'] ?? '') === 'file') {
                    $hasFileFields = true;
                }

                // Collect related models for imports
                if (($field['type'] ?? '') === 'select' &&
                    ($field['select_source'] ?? 'manual') === 'database' &&
                    !empty($field['related_table'])
                ) {

                    $relatedTable = $field['related_table'];
                    $modelName = Str::studly(Str::singular($relatedTable));
                    $pluralModelName = Str::studly($relatedTable);

                    // Add both possible model paths to imports
                    $modelPath1 = "App\\Models\\{$pluralModelName}\\{$pluralModelName}";
                    $modelPath2 = "App\\Models\\{$modelName}\\{$modelName}";

                    if (!in_array($modelPath1, $relatedModels)) {
                        $relatedModels[] = $modelPath1;
                    }
                    if (!in_array($modelPath2, $relatedModels)) {
                        $relatedModels[] = $modelPath2;
                    }
                }
            }
        }

        $fileUploadUse = $hasFileFields ? "\nuse Livewire\\WithFileUploads;" : "";
        $fileUploadTrait = $hasFileFields ? "\n    use WithFileUploads;" : "";

        // Generate related models imports
        $relatedModelsUse = "";
        foreach ($relatedModels as $modelPath) {
            $relatedModelsUse .= "\nuse {$modelPath};";
        }

        $content = "<?php

namespace App\\Http\\Livewire\\{$name};

use Livewire\\Component;
use Livewire\\WithPagination;{$fileUploadUse}
use App\\Models\\Tracking\\Tracking;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\\Excel\\Facades\\Excel;
use App\\Exports\\{$name}Export;
use PhpOffice\\PhpSpreadsheet\\Spreadsheet;
use PhpOffice\\PhpSpreadsheet\\Style\\Fill;
use PhpOffice\\PhpSpreadsheet\\Style\\Alignment;
use PhpOffice\\PhpSpreadsheet\\Style\\Border;
use PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx;
use App\\Models\\{$name}\\{$name} as {$singularName}Model;
use App\\Models\\System\\ModuleField;{$relatedModelsUse}

class {$singularName} extends Component
{
    use WithPagination;{$fileUploadTrait}
    protected \$paginationTheme = 'bootstrap';

    public \${$name} = [];
    public \${$lowerSingular};
    {$properties}
    public \$search = {$searchArray};
    public \$selectedRows = [];
    public \$selectAll = false;

    public function updatedSearch(\$value, \$key)
    {
        if (in_array(\$key, [{$searchFields}])) {
            \$this->resetPage();
        }
    }

    public function mount()
    {" . $this->generateMountCalculationCode($fields) . "
    }

    public function render()
    {

        {$searchFilter}
        \${$name} = {$singularName}Model::query()
            {$queryCondition}

            ->orderBy('id', 'ASC')
            ->paginate(10);

        \$links = \${$name};
        \$this->{$name} = collect(\${$name}->items());

        return view('livewire.{$kebabName}.{$kebabSingular}', [
            '{$name}' => \${$name},
            'links' => \$links,
            '_instance' => \$this
        ]);
    }

    /* Get validation rules for store (إضافة جديدة) */
    private function getStoreRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً
            \$rules = ModuleField::getValidationRules('{$name}', false);
            return \$rules ?: [
                {$fallbackStoreRules}
            ];
        } catch (\\Exception \$e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                {$fallbackStoreRules}
            ];
        }
    }

    /* Get validation rules for update (تعديل موجود) */
    private function getUpdateRules()
    {
        try {
            // محاولة جلب القواعد من قاعدة البيانات أولاً مع معرف السجل للتحقق من unique
            \$rules = ModuleField::getValidationRules('{$name}', true, \$this->{$lowerSingular}->id ?? null);
            return \$rules ?: [
                {$fallbackUpdateRules}
            ];
        } catch (\\Exception \$e) {
            // في حالة الخطأ، استخدام القواعد الاحتياطية
            return [
                {$fallbackUpdateRules}
            ];
        }
    }


    /* Get validation messages */
    private function getValidationMessages()
    {
        try {
            \$messages = ModuleField::getValidationMessages('{$name}');
            return \$messages ?: \$this->getFallbackMessages();
        } catch (\\Exception \$e) {
            return \$this->getFallbackMessages();
        }
    }

    /* Get fallback validation messages */
    private function getFallbackMessages()
    {
        return [
            {$fallbackStoreMessages}
        ];
    }

    public function Add{$singularName}ModalShow()
    {
        \$this->reset();
        \$this->resetValidation();
        \$this->dispatchBrowserEvent('{$singularName}ModalShow');
    }


    public function store()
    {
        try {
            \$this->resetValidation();
            \$this->validate(\$this->getStoreRules(), \$this->getValidationMessages());

            // Handle file uploads
            \$fileData = [];
            {$fileUploadCode}

            {$singularName}Model::create(array_merge([
                {$createFields}
            ], \$fileData));
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => '{$arName}',
                'operation_type' => 'اضافة',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => {$detailsText},
            ]);
            // =================================
            \$this->reset();
            \$this->dispatchBrowserEvent('success', [
                'message' => 'تم الاضافه بنجاح',
                'title' => 'اضافه'
            ]);
        } catch (ValidationException \$e) {
            // Re-throw validation exceptions to show field-specific errors
            throw \$e;
        } catch (\Exception \$e) {
            \$this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء الإضافة: ' . \$e->getMessage(),
                'title' => 'خطأ'
            ]);
        }
    }

    public function Get{$singularName}(\${$lowerSingular}Id)
    {
        \$this->resetValidation();

        \$this->{$lowerSingular}  = {$singularName}Model::find(\${$lowerSingular}Id);
        {$fieldAssignments}

" . $this->generateGetEmployeeCalculationCode($fields) . "

        // Dispatch event to notify frontend that data is loaded
        \$this->dispatchBrowserEvent('{$lowerSingular}DataLoaded');
    }

    public function update()
    {
        try {
            \$this->resetValidation();
            \$this->validate(\$this->getUpdateRules(), \$this->getValidationMessages());

            \${$singularName} = {$singularName}Model::find(\$this->{$lowerSingular}->id ?? null);
            if (!\${$singularName}) {
                \$this->dispatchBrowserEvent('error', [
                    'message' => 'البيانات المطلوبة غير موجودة',
                    'title' => 'خطأ'
                ]);
                return;
            }

            \$updateData = [
                {$updateFields}
            ];

            {$updateFileUploadCode}

            \${$singularName}->update(\$updateData);
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => '{$arName}',
                'operation_type' => 'تعديل',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => \"تم تعديل {$arName}\",
            ]);
            // =================================
            \$this->reset();
            \$this->dispatchBrowserEvent('success', [
                'message' => 'تم التعديل بنجاح',
                'title' => 'تعديل'
            ]);
        } catch (ValidationException \$e) {
            // Re-throw validation exceptions to show field-specific errors
            throw \$e;
        } catch (\Exception \$e) {
            \$this->dispatchBrowserEvent('error', [
                'message' => 'حدث خطأ أثناء التعديل: ' . \$e->getMessage(),
                'title' => 'خطأ'
            ]);
        }
    }

    public function destroy()
    {
        \${$singularName} = {$singularName}Model::find(\$this->{$lowerSingular}->id ?? null);

        if (\${$singularName}) {
            // =================================
            Tracking::create([
                'user_id' => Auth::user()->id,
                'page_name' => '{$arName}',
                'operation_type' => 'حذف',
                'operation_time' => now()->format('Y-m-d H:i:s'),
                'details' => {$deleteDetailsText},
            ]);
            // =================================
            \${$singularName}->delete();
            \$this->reset();
            \$this->dispatchBrowserEvent('success', [
                'message' => 'تم حذف البيانات بنجاح',
                'title' => 'الحذف'
            ]);
        }
    }

    // Export to Excel
    public function exportExcel()
    {
        \$fileName = '{$arName}_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new {$name}Export, \$fileName);
    }

    // PDF export methods are now handled by dedicated controllers:
    // - {$singularName}TcpdfExportController for TCPDF export
    // - {$singularName}PrintController for direct printing

    public function updatedSelectAll(\$value)
    {
        if (\$value) {
            \$this->selectedRows = {$singularName}Model::pluck('id')->map(fn(\$id) => (string) \$id)->toArray();
        } else {
            \$this->selectedRows = [];
        }
    }

    public function updatedSelectedRows(\$value)
    {
        \$totalCount = {$singularName}Model::count();
        \$this->selectAll = count(\$this->selectedRows) === \$totalCount;
    }

    public function exportSelected()
    {
        if (empty(\$this->selectedRows)) {
            \$this->dispatchBrowserEvent('error', [
                'title' => 'خطأ',
                'message' => 'الرجاء تحديد صف واحد على الأقل'
            ]);
            return;
        }

        \$spreadsheet = new Spreadsheet();
        \$sheet = \$spreadsheet->getActiveSheet();
        \$sheet->setRightToLeft(true);

        // Set headers
" . $this->getHeadersString($fields) . "
        \$sheet->fromArray([\$headers], NULL, 'A1');

        // Header styling
        \$headerStyle = [
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
        \$sheet->getStyle('A1:' . chr(64 + count(\$headers)) . '1')->applyFromArray(\$headerStyle);

        // Add data
        \$row = 2;
        \$items = {$singularName}Model::whereIn('id', \$this->selectedRows)->get();
        foreach (\$items as \$item) {
" . $this->getDataRowsString($fields) . "
            \$sheet->fromArray([\$data], NULL, 'A' . \$row);
            \$row++;
        }

        // Data styling
        \$dataRange = 'A2:' . chr(64 + count(\$headers)) . (\$row - 1);
        \$dataStyle = [
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
        \$sheet->getStyle(\$dataRange)->applyFromArray(\$dataStyle);

        // Auto-size columns
        foreach (range('A', chr(64 + count(\$headers))) as \$column) {
            \$sheet->getColumnDimension(\$column)->setAutoSize(true);
        }

        \$fileName = '{$lowerName}_' . date('Y-m-d_H-i-s') . '.xlsx';
        \$writer = new Xlsx(\$spreadsheet);

        \$path = storage_path('app/public/exports');
        if (!file_exists(\$path)) {
            mkdir(\$path, 0777, true);
        }

        \$fullPath = \$path . '/' . \$fileName;
        \$writer->save(\$fullPath);

        return response()->download(\$fullPath)->deleteFileAfterSend();
    }
}";

        // إضافة دوال العمليات الحسابية إذا كان هناك حقول محسوبة
        $calculationMethods = $this->generateCalculationMethods($fields, $name);
        if (!empty($calculationMethods)) {
            // إضافة methods قبل آخر قوس إغلاق للكلاس
            $lastBracePos = strrpos($content, '}');
            if ($lastBracePos !== false) {
                $content = substr($content, 0, $lastBracePos) . $calculationMethods . PHP_EOL . '}';
                $this->info("🧮 تم إضافة methods الحسابية");
            }
        }        // Generate TCPDF template variables
        $tcpdfHeaders = $this->getTcpdfHeadersString($fields);
        $tcpdfData = $this->getTcpdfDataString($fields);

        // Replace template variables in content
        $content = str_replace('{$tcpdfHeaders}', $tcpdfHeaders, $content);
        $content = str_replace('{$tcpdfData}', $tcpdfData, $content);
        $content = str_replace('{$fallbackStoreRules}', $fallbackStoreRules, $content);
        $content = str_replace('{$fallbackStoreMessages}', $fallbackStoreMessages, $content);
        $content = str_replace('{$fallbackUpdateRules}', $fallbackUpdateRules, $content);

        $path = base_path("app/Http/Livewire/{$name}/{$singularName}.php");
        File::put($path, $content);
        $this->info("🔧 Created Livewire component");
    }

    /**
     * إنشاء دوال العمليات الحسابية
     */
    protected function generateCalculationMethods($fields, $name)
    {
        // التحقق من وجود حقول محسوبة
        $hasCalculatedFields = false;
        foreach ($fields as $field) {
            if ($field['is_calculated'] ?? false) {
                $hasCalculatedFields = true;
                break;
            }
        }

        if (!$hasCalculatedFields) {
            return '';
        }

        $calculatedFieldsMethods = "
    /**
     * حساب قيم الحقول المحسوبة باستخدام إعدادات قاعدة البيانات
     */
    public function calculateFields()
    {
        try {
            // جلب إعدادات الحقول المحسوبة من قاعدة البيانات
            \$calculatedFields = ModuleField::where('module_name', '{$name}')
                ->where(function(\$query) {
                    \$query->where('calculation_type', 'date_diff')
                           ->where('is_date_calculated', true)
                           ->orWhere('calculation_type', 'time_diff')
                           ->where('is_time_calculated', true)
                           ->orWhere('is_calculated', true);
                })
                ->get();

            foreach (\$calculatedFields as \$fieldConfig) {
                try {
                    \$fieldName = \$fieldConfig->field_name;
                    \$calculationType = \$fieldConfig->calculation_type;

                    if (\$calculationType === 'date_diff' && \$fieldConfig->is_date_calculated) {
                        \$fromFieldName = \$fieldConfig->date_from_field;
                        \$toFieldName = \$fieldConfig->date_to_field;
                        \$unit = \$fieldConfig->date_diff_unit;
                        \$remainingOnly = \$fieldConfig->remaining_only;
                        \$includeEndDate = \$fieldConfig->include_end_date;

                        \$fromValue = \$this->\$fromFieldName ?? null;
                        \$toValue = \$this->\$toFieldName ?? null;

                        if (\$fromValue && \$toValue) {
                            \$fromDate = \\Carbon\\Carbon::parse(\$fromValue);
                            \$toDate = \\Carbon\\Carbon::parse(\$toValue);

                            \$result = 0;

                            switch (\$unit) {
                                case 'days':
                                    if (\$remainingOnly) {
                                        // حساب الأيام المتبقية من diff (بعد استخراج السنوات والأشهر)
                                        \$diff = \$fromDate->diff(\$toDate);
                                        \$result = \$diff->d; // الأيام المتبقية فقط
                                        if (\$includeEndDate) \$result += 1;
                                    } else {
                                        // إجمالي الأيام بين التاريخين
                                        \$result = \$fromDate->diffInDays(\$toDate, false);
                                        if (\$includeEndDate) \$result += 1;
                                    }
                                    break;

                                case 'months':
                                    if (\$remainingOnly) {
                                        // حساب الأشهر المتبقية من diff (بعد استخراج السنوات)
                                        \$diff = \$fromDate->diff(\$toDate);
                                        \$result = \$diff->m; // الأشهر المتبقية فقط
                                        if (\$includeEndDate) \$result += 1;
                                    } else {
                                        // إجمالي الأشهر بين التاريخين
                                        \$result = \$fromDate->diffInMonths(\$toDate, false);
                                        if (\$includeEndDate) \$result += 1;
                                    }
                                    break;

                                case 'years':
                                    if (\$remainingOnly) {
                                        // حساب السنوات من diff
                                        \$diff = \$fromDate->diff(\$toDate);
                                        \$result = \$diff->y; // السنوات
                                        if (\$includeEndDate) \$result += 1;
                                    } else {
                                        // إجمالي السنوات بين التاريخين
                                        \$result = \$fromDate->diffInYears(\$toDate, false);
                                        if (\$includeEndDate) \$result += 1;
                                    }
                                    break;

                                default:
                                    \$result = 0;
                            }

                            \$this->\$fieldName = \$result;
                        } else {
                            \$this->\$fieldName = 0;
                        }
                    } elseif (\$calculationType === 'time_diff' && \$fieldConfig->is_time_calculated) {
                        // حساب الفرق بين وقتين
                        \$fromFieldName = \$fieldConfig->time_from_field;
                        \$toFieldName = \$fieldConfig->time_to_field;
                        \$unit = \$fieldConfig->time_diff_unit;
                        \$absoluteValue = \$fieldConfig->absolute_value;
                        \$remainingOnly = \$fieldConfig->remaining_only;

                        \$fromValue = \$this->\$fromFieldName ?? null;
                        \$toValue = \$this->\$toFieldName ?? null;

                        if (\$fromValue && \$toValue) {
                            try {
                                // تحويل الأوقات إلى كائنات Carbon
                                \$fromTime = \\Carbon\\Carbon::createFromTimeString(\$fromValue);
                                \$toTime = \\Carbon\\Carbon::createFromTimeString(\$toValue);

                                \$result = 0;

                                switch (\$unit) {
                                    case 'hours':
                                        \$result = \$fromTime->diffInHours(\$toTime, false);
                                        // تطبيق خاصية الساعات المتبقية فقط
                                        if (\$remainingOnly) {
                                            \$result = \$result % 24; // الساعات المتبقية بعد الأيام الكاملة
                                        }
                                        break;

                                    case 'minutes':
                                    default:
                                        \$result = \$fromTime->diffInMinutes(\$toTime, false);
                                        // تطبيق خاصية الدقائق المتبقية فقط
                                        if (\$remainingOnly) {
                                            \$result = \$result % 60; // الدقائق المتبقية بعد الساعات الكاملة
                                        }
                                        break;
                                }

                                // تطبيق القيمة المطلقة إذا كانت مطلوبة
                                if (\$absoluteValue) {
                                    \$result = abs(\$result);
                                }

                                \$this->\$fieldName = \$result;
                            } catch (\\Exception \$timeError) {
                                \$this->\$fieldName = 0;
                                Log::error(\"خطأ في حساب الوقت للحقل {\$fieldName}: \" . \$timeError->getMessage());
                            }
                        } else {
                            \$this->\$fieldName = 0;
                        }
                    } elseif (\$calculationType === 'formula' && \$fieldConfig->is_calculated) {
                        // حساب المعادلات الحسابية العادية
                        \$formula = \$fieldConfig->calculation_formula ?? '';
                        \$this->\$fieldName = \$this->evaluateFormula(\$formula);
                    }
                } catch (\\Exception \$e) {
                    \$this->\$fieldName = 0;
                    Log::error(\"خطأ في حساب الحقل {\$fieldName}: \" . \$e->getMessage());
                }
            }
        } catch (\\Exception \$e) {
            \$this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في حساب القيم: ' . \$e->getMessage(),
                'title' => 'خطأ في الحساب'
            ]);
        }
    }

    /**
     * تقييم معادلة الحساب
     */
    private function evaluateFormula(\$formula)
    {
        if (empty(\$formula)) {
            return 0;
        }

        try {
            // استبدال أسماء الحقول بقيمها الفعلية
            \$processedFormula = \$this->replaceFieldsInFormula(\$formula);

            // التحقق من وجود قسمة على صفر قبل التقييم
            if (\$this->hasDivisionByZero(\$processedFormula)) {
                return 0;
            }

            // تنظيف المعادلة من الأحرف غير المرغوب فيها
            \$sanitizedFormula = \$this->sanitizeFormula(\$processedFormula);

            // التحقق مرة أخرى بعد التنظيف
            if (empty(\$sanitizedFormula) || \$this->hasDivisionByZero(\$sanitizedFormula)) {
                return 0;
            }

            // تقييم المعادلة الرياضية
            \$result = eval(\"return \$sanitizedFormula;\");

            // التحقق من أن النتيجة رقم صحيح
            if (!is_numeric(\$result) || is_infinite(\$result) || is_nan(\$result)) {
                return 0;
            }

            return round(\$result, 2);
        } catch (\\Exception \$e) {
            return 0;
        } catch (\\DivisionByZeroError \$e) {
            return 0;
        } catch (\\ParseError \$e) {
            return 0;
        }
    }

    /**
     * التحقق من وجود قسمة على صفر في المعادلة
     */
    private function hasDivisionByZero(\$formula)
    {
        // البحث عن أنماط القسمة على صفر
        if (preg_match('/\\/\\s*0(?![0-9])/', \$formula)) {
            return true;
        }

        // البحث عن قسمة على صفر مع مسافات
        if (preg_match('/\\/\\s*0\\s*[\\+\\-\\*\\/\\)\\s]/', \$formula)) {
            return true;
        }

        // البحث عن قسمة على صفر في نهاية المعادلة
        if (preg_match('/\\/\\s*0\\s*$/', \$formula)) {
            return true;
        }

        return false;
    }

    /**
     * استبدال أسماء الحقول في المعادلة بقيمها
     */
    private function replaceFieldsInFormula(\$formula)
    {";

        foreach ($fields as $field) {
            // شمل الحقول الرقمية العادية وحقول select التي لها قيم رقمية
            if (in_array($field['type'], ['integer', 'decimal']) ||
                ($field['type'] === 'select' && isset($field['select_numeric_values']) && $field['select_numeric_values'])) {
                $fieldName = $field['name'];
                $fieldTypeComment = $field['type'] === 'select' ? ' للحقل ' . $fieldName . ' (قائمة منسدلة رقمية)' : ' للحقل ' . $fieldName;
                $calculatedFieldsMethods .= "
        // التأكد من أن القيمة رقمية صحيحة{$fieldTypeComment}
        \$value_{$fieldName} = \$this->{$fieldName};
        if (!is_numeric(\$value_{$fieldName}) || \$value_{$fieldName} === '' || \$value_{$fieldName} === null) {
            \$value_{$fieldName} = 0;
        }
        \$formula = str_replace('{$fieldName}', \$value_{$fieldName}, \$formula);";
            }
        }

        $calculatedFieldsMethods .= "
        return \$formula;
    }

    /**
     * تنظيف المعادلة من الأحرف غير المرغوب فيها
     */
    private function sanitizeFormula(\$formula)
    {
        // السماح بالأرقام والعمليات الحسابية والأقواس والمسافات والنقطة العشرية فقط
        return preg_replace('/[^0-9+\\-*\\/(). ]/', '', \$formula);
    }
";

        // إضافة دوال updated للحقول الرقمية وحقول select الرقمية
        foreach ($fields as $field) {
            if (in_array($field['type'], ['integer', 'decimal']) ||
                ($field['type'] === 'select' && isset($field['select_numeric_values']) && $field['select_numeric_values'])) {
                $fieldName = $field['name'];
                $methodName = 'updated' . str_replace('_', '', ucwords($fieldName, '_'));

                $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$field['ar_name']}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
            }
        }

        // إضافة دوال updated للحقول المستخدمة في حسابات التاريخ والوقت
        $usedFields = []; // قائمة موحدة لتجنب التكرار

        foreach ($fields as $field) {
            // التحقق من حقول حسابات التاريخ
            if (($field['calculation_type'] ?? '') === 'date_diff') {
                $dateFromField = $field['date_from_field'] ?? '';
                $dateToField = $field['date_to_field'] ?? '';

                if ($dateFromField && !in_array($dateFromField, $usedFields)) {
                    $usedFields[] = $dateFromField;
                    $methodName = 'updated' . str_replace('_', '', ucwords($dateFromField, '_'));
                    $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$dateFromField}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
                }

                if ($dateToField && !in_array($dateToField, $usedFields)) {
                    $usedFields[] = $dateToField;
                    $methodName = 'updated' . str_replace('_', '', ucwords($dateToField, '_'));
                    $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$dateToField}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
                }
            }

            // التحقق من حقول حسابات الوقت
            if (($field['calculation_type'] ?? '') === 'time_diff') {
                $timeFromField = $field['time_from_field'] ?? '';
                $timeToField = $field['time_to_field'] ?? '';

                if ($timeFromField && !in_array($timeFromField, $usedFields)) {
                    $usedFields[] = $timeFromField;
                    $methodName = 'updated' . str_replace('_', '', ucwords($timeFromField, '_'));
                    $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$timeFromField}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
                }

                if ($timeToField && !in_array($timeToField, $usedFields)) {
                    $usedFields[] = $timeToField;
                    $methodName = 'updated' . str_replace('_', '', ucwords($timeToField, '_'));
                    $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$timeToField}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
                }
            }
        }

        return $calculatedFieldsMethods;
    }

    /**
     * توليد كود mount للحقول المحسوبة
     */
    protected function generateMountCalculationCode($fields)
    {
        // التحقق من وجود حقول محسوبة
        $hasCalculatedFields = false;
        foreach ($fields as $field) {
            if ($field['is_calculated'] ?? false) {
                $hasCalculatedFields = true;
                break;
            }
        }

        if ($hasCalculatedFields) {
            return "
        // حساب الحقول المحسوبة عند تحميل المكون
        \$this->calculateFields();";
        }

        return '';
    }

    /**
     * توليد كود Get{$singularName} للحقول المحسوبة
     */
    protected function generateGetEmployeeCalculationCode($fields)
    {
        // التحقق من وجود حقول محسوبة
        $hasCalculatedFields = false;
        foreach ($fields as $field) {
            if ($field['is_calculated'] ?? false) {
                $hasCalculatedFields = true;
                break;
            }
        }

        if ($hasCalculatedFields) {
            return "        // حساب الحقول المحسوبة عند تحميل البيانات
        \$this->calculateFields();";
        }

        return '';
    }

    /**
     * توليد كود حساب فرق التواريخ
     */
    protected function generateDateCalculationCode($field)
    {
        $fieldName = $field['name'];
        $arName = $field['ar_name'];
        $fromField = $field['date_from_field'] ?? '';
        $toField = $field['date_to_field'] ?? '';
        $unit = $field['date_diff_unit'] ?? 'days';
        $includeEndDate = $field['include_end_date'] ?? false ? 'true' : 'false';
        $absoluteValue = $field['absolute_value'] ?? false ? 'true' : 'false';
        $remainingOnly = $field['remaining_only'] ?? false ? 'true' : 'false';

        return "
            // حساب {$arName} ({$fieldName}) - فرق التواريخ
            try {
                if (\$this->{$fromField} && \$this->{$toField}) {
                    \$fromDate = \\Carbon\\Carbon::parse(\$this->{$fromField});
                    \$toDate = \\Carbon\\Carbon::parse(\$this->{$toField});

                    if ({$remainingOnly}) {
                        // حساب المتبقي فقط باستخدام diff
                        \$diff = \$fromDate->diff(\$toDate);
                        if ('{$unit}' === 'days') {
                            \$result = \$diff->d; // الأيام المتبقية فقط
                        } elseif ('{$unit}' === 'months') {
                            \$result = \$diff->m; // الأشهر المتبقية فقط
                        } elseif ('{$unit}' === 'years') {
                            \$result = \$diff->y; // السنوات فقط
                        } else {
                            \$result = 0;
                        }
                        if ({$includeEndDate}) \$result += 1;
                    } else {
                        // حساب كامل - إجمالي الفرق بالوحدة المحددة
                        \$result = \$fromDate->diffIn" . ucfirst($unit) . "(\$toDate, {$absoluteValue});
                        if ({$includeEndDate} && '{$unit}' === 'days') \$result += 1;
                    }

                    if ({$absoluteValue}) {
                        \$result = abs(\$result);
                    }

                    \$this->{$fieldName} = \$result;
                } else {
                    \$this->{$fieldName} = 0;
                }
            } catch (\\Exception \$e) {
                \$this->{$fieldName} = 0;
                \\Illuminate\\Support\\Facades\\Log::error('خطأ في حساب التاريخ للحقل {$fieldName}: ' . \$e->getMessage());
            }";
    }

    /**
     * توليد JavaScript callbacks للحقول المحسوبة للتاريخ
     */
    protected function generateDateCalculationCallbacks($fields)
    {
        $callbacks = "";
        $processedDateFields = [];

        foreach ($fields as $field) {
            if (($field['is_calculated'] ?? false) && ($field['calculation_type'] ?? '') === 'date_diff') {
                $fromField = $field['date_from_field'] ?? '';
                $toField = $field['date_to_field'] ?? '';

                // إضافة callback لحقل التاريخ الأول
                if ($fromField && !in_array($fromField, $processedDateFields)) {
                    $callbacks .= "
            // Callback لحقل {$fromField}
            \$(document).on('change', 'input[name=\"{$fromField}\"], input[id=\"{$fromField}\"]', function() {
                var componentId = this.closest('[wire\\\\:id]').getAttribute('wire:id');
                if (window.livewire && window.livewire.find(componentId)) {
                    window.livewire.find(componentId).set('{$fromField}', this.value);
                    window.livewire.find(componentId).call('calculateFields');
                }
            });";
                    $processedDateFields[] = $fromField;
                }

                // إضافة callback لحقل التاريخ الثاني
                if ($toField && !in_array($toField, $processedDateFields)) {
                    $callbacks .= "
            // Callback لحقل {$toField}
            \$(document).on('change', 'input[name=\"{$toField}\"], input[id=\"{$toField}\"]', function() {
                var componentId = this.closest('[wire\\\\:id]').getAttribute('wire:id');
                if (window.livewire && window.livewire.find(componentId)) {
                    window.livewire.find(componentId).set('{$toField}', this.value);
                    window.livewire.find(componentId).call('calculateFields');
                }
            });";
                    $processedDateFields[] = $toField;
                }
            }
        }

        return $callbacks;
    }

    protected function getHeadersString($fields)
    {
        if (empty($fields)) {
            return "
        \$headers = ['ID', 'الاسم'];";
        }

        $headerItems = ["'ID'"];
        foreach ($fields as $field) {
            $label = $field['ar_name'] ?? $field['name'];
            $headerItems[] = "'" . addslashes($label) . "'";
        }

        return "
        \$headers = [" . implode(', ', $headerItems) . "];";
    }

    protected function getDataRowsString($fields)
    {
        if (empty($fields)) {
            return "
            \$data = [\$item->id, \$item->name];";
        }

        $dataElements = ['$item->id'];
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (($field['type'] ?? 'text') === 'checkbox') {
                $trueLabel = $field['checkbox_true_label'] ?? 'مفعل';
                $falseLabel = $field['checkbox_false_label'] ?? 'غير مفعل';
                $dataElements[] = '$item->' . $fieldName . ' ? \'' . addslashes($trueLabel) . '\' : \'' . addslashes($falseLabel) . '\'';
            } elseif (($field['type'] ?? 'text') === 'date') {
                $dataElements[] = '$item->' . $fieldName . ' ? \Carbon\Carbon::parse($item->' . $fieldName . ')->format(\'Y/m/d\') : \'\'';
            } elseif (($field['type'] ?? 'text') === 'datetime') {
                $dataElements[] = '$item->' . $fieldName . ' ? \Carbon\Carbon::parse($item->' . $fieldName . ')->format(\'Y/m/d H:i\') : \'\'';
            } elseif (($field['type'] ?? 'text') === 'time') {
                $dataElements[] = '$item->' . $fieldName . ' ?? \'\'';
            } elseif (($field['type'] ?? 'text') === 'month_year') {
                $dataElements[] = '$item->' . $fieldName . ' ?? \'\'';
            } else {
                $dataElements[] = '$item->' . $fieldName;
            }
        }

        return "
            \$data = [" . implode(', ', $dataElements) . "];";
    }

    protected function getTcpdfHeadersString($fields)
    {
        if (empty($fields)) {
            return "
            \$pdf->Cell(20, 10, 'الرقم', 1, 0, 'C', 1);
            \$pdf->Cell(170, 10, 'الاسم', 1, 1, 'C', 1);";
        }

        $headerCells = ["\$pdf->Cell(20, 10, 'الرقم', 1, 0, 'C', 1);"];
        $totalWidth = 20; // Start with ID column width
        $remainingWidth = 170; // Total available width minus ID column
        $cellWidth = floor($remainingWidth / count($fields));

        foreach ($fields as $index => $field) {
            $label = $field['ar_name'] ?? $field['name'];
            $isLast = ($index === count($fields) - 1) ? '1' : '0';
            $headerCells[] = "\$pdf->Cell({$cellWidth}, 10, '" . addslashes($label) . "', 1, {$isLast}, 'C', 1);";
        }

        return "
            " . implode("\n            ", $headerCells);
    }

    protected function getTcpdfDataString($fields)
    {
        if (empty($fields)) {
            return "
                \$pdf->Cell(20, 8, \$item->id ?? '', 1, 0, 'C', 1);
                \$pdf->Cell(170, 8, \$item->name ?? 'غير محدد', 1, 1, 'C', 1);";
        }

        $dataCells = ["\$pdf->Cell(20, 8, \$item->id ?? '', 1, 0, 'C', 1);"];
        $remainingWidth = 170;
        $cellWidth = floor($remainingWidth / count($fields));

        foreach ($fields as $index => $field) {
            $fieldName = $field['name'];
            $isLast = ($index === count($fields) - 1) ? '1' : '0';

            if (($field['type'] ?? 'text') === 'checkbox') {
                $trueLabel = $field['checkbox_true_label'] ?? 'مفعل';
                $falseLabel = $field['checkbox_false_label'] ?? 'غير مفعل';
                $dataCells[] = "\$pdf->Cell({$cellWidth}, 8, \$item->{$fieldName} ? '" . addslashes($trueLabel) . "' : '" . addslashes($falseLabel) . "', 1, {$isLast}, 'C', 1);";
            } elseif (($field['type'] ?? 'text') === 'date') {
                $dataCells[] = "\$pdf->Cell({$cellWidth}, 8, \$item->{$fieldName} ? \\Carbon\\Carbon::parse(\$item->{$fieldName})->format('Y/m/d') : 'غير محدد', 1, {$isLast}, 'C', 1);";
            } else {
                $dataCells[] = "\$pdf->Cell({$cellWidth}, 8, \$item->{$fieldName} ?? 'غير محدد', 1, {$isLast}, 'C', 1);";
            }
        }

        return "
                " . implode("\n                ", $dataCells);
    }

    protected function createViews($name, $fields, $arName)
    {
        $singularName = Str::singular($name);
        $lowerSingular = strtolower($singularName);
        $lowerName = strtolower($name);

        // متغيرات جديدة لـ Livewire kebab-case
        $kebabName = Str::kebab($name);
        $kebabSingular = Str::kebab($singularName);

        // Create main view (content)
        $mainView = "@extends('layouts/layoutMaster')
@section('title', '{$arName}')
@section('vendor-style')
    <link rel=\"stylesheet\"href=\"{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}\">
    <link rel=\"stylesheet\"href=\"{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}\">
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}\">
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css') }}\">
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/select2/select2.css') }}\" />
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}\" />
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/animate-css/animate.css') }}\" />
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}\" />
    <link rel=\"stylesheet\" href=\"{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}\" />
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css\">
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css\" />
@endsection
@section('content')

    @livewire('{$kebabName}.{$kebabSingular}')

@endsection

@section('vendor-script')
    <script src=\"{{ asset('assets/vendor/libs/moment/moment.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/select2/select2.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}\"></script>
    <script src=\"{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/flatpickr\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js\"></script>
    <script src=\"https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js\"></script>
@endsection

@section('page-script')
    <script src=\"{{ asset('assets/js/app-user-list.js') }}\"></script>
    <script src=\"{{ asset('assets/js/extended-ui-sweetalert2.js') }}\"></script>
    <script src=\"{{ asset('assets/js/form-basic-inputs.js') }}\"></script>
    <script>
        // Initialize Flatpickr for date fields
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr for all date inputs
            flatpickr('.flatpickr-date', {
                dateFormat: 'Y-m-d',
                locale: 'ar'
            });

            flatpickr('.flatpickr-datetime', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i:S',
                locale: 'ar',
                time_24hr: true
            });

            // Month/Year picker - using monthSelectPlugin
            flatpickr('.flatpickr-month-year', {
                placeholder: 'التاريخ',
                altInput: true,
                allowInput: true,
                dateFormat: 'Y-m',
                altFormat: 'F Y',
                yearSelectorType: 'input',
                locale: {
                    months: {
                        shorthand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ],
                        longhand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ]
                    }
                },
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true,
                        dateFormat: 'Y-m',
                        altFormat: 'F Y',
                        theme: 'light'
                    })
                ]
            });

            // Initialize Select2 for search fields
            $('.select2-search').select2({
                placeholder: 'بحث...',
                allowClear: true,
                width: '100%'
            });

            // إضافة callbacks للحقول المحسوبة للتاريخ
            initializeDateCalculationCallbacks();
        });

        // دالة تفعيل callbacks للحقول المحسوبة للتاريخ
        function initializeDateCalculationCallbacks() {" . $this->generateDateCalculationCallbacks($fields) . "
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        window.addEventListener('{$singularName}ModalShow', event => {
            setTimeout(() => {
                $('#id').focus();
            }, 100);
        })

        window.addEventListener('success', event => {
            $('#add{$lowerSingular}Modal').modal('hide');
            $('#edit{$lowerSingular}Modal').modal('hide');
            $('#remove{$lowerSingular}Modal').modal('hide');

            // تنظيف مؤشرات الملفات عند النجاح
            setTimeout(() => {
                clearFileIndicators('');
                clearFileIndicators('Edit');
            }, 500);

            Toast.fire({
                icon: 'success',
                title: event.detail.title + '<hr>' + event.detail.message,
            })
        })

        window.addEventListener('error', event => {
            $('#remove{$lowerSingular}Modal').modal('hide');
            Toast.fire({
                icon: 'error',
                title: event.detail.title + '<hr>' + event.detail.message,
                timer: 8000,
            })
        })

        // Print file function - طباعة مع معالجة خاصة للـ PDF
    function printFile(fileUrl) {
        if (!fileUrl) {
            alert('لا يوجد ملف للطباعة');
            return;
        }

        // تحديد نوع الملف
        const fileExtension = fileUrl.split('.').pop().toLowerCase();
        const isPDF = fileExtension === 'pdf';

        if (isPDF) {
            // للـ PDF فتح في نافذة جديدة مع إعطاء المستخدم التحكم الكامل
            const printWindow = window.open(
                fileUrl,
                '_blank',
                'width=1000,height=700,scrollbars=yes,resizable=yes,toolbar=yes,menubar=yes'
            );

            if (printWindow) {
                // إعطاء المستخدم وقت لرؤية الملف قبل عرض نافذة الطباعة
                printWindow.addEventListener('load', function() {
                    setTimeout(() => {
                        printWindow.focus();
                        // عرض نافذة الطباعة دون إغلاق النافذة تلقائياً
                        printWindow.print();
                        // السماح للمستخدم بإغلاق النافذة بنفسه
                    }, 1500);
                });

                // backup timeout في حالة عدم تحميل الـ load event
                setTimeout(() => {
                    if (printWindow && !printWindow.closed) {
                        try {
                            printWindow.focus();
                            printWindow.print();
                        } catch (e) {
                            console.log('PDF print backup failed:', e);
                        }
                    }
                }, 3000);
            } else {
                alert('فشل في فتح نافذة الطباعة. تحقق من إعدادات النوافذ المنبثقة.');
            }
        } else {
            // للصور والملفات الأخرى - iframe مخفي
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.left = '-9999px';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.src = fileUrl;

            document.body.appendChild(iframe);

            iframe.onload = function() {
                setTimeout(() => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        setTimeout(() => {
                            if (document.body.contains(iframe)) {
                                document.body.removeChild(iframe);
                            }
                        }, 1000);
                    } catch (e) {
                        console.log('Image print failed:', e);
                        const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                        if (printWindow) {
                            printWindow.onload = function() {
                                printWindow.print();
                                printWindow.close();
                            };
                        }
                        if (document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }
                }, 500);
            };

            iframe.onerror = function() {
                console.log('Image iframe load failed');
                const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                if (printWindow) {
                    printWindow.onload = function() {
                        printWindow.print();
                        printWindow.close();
                    };
                }
                if (document.body.contains(iframe)) {
                    document.body.removeChild(iframe);
                }
            };
        }
    }

        // دالة طباعة PDF - بساطة مثل زر العرض
        function printPDF(fileUrl) {
            // فتح PDF في نافذة جديدة مع خيارات طباعة محسنة
            const printWindow = window.open(
                fileUrl,
                '_blank',
                'width=1000,height=700,scrollbars=yes,resizable=yes,toolbar=yes,menubar=yes'
            );

            if (printWindow) {
                // تركيز على النافذة الجديدة ثم عرض خيارات الطباعة
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 2000);
            } else {
                alert('فشل في فتح نافذة الطباعة. تحقق من إعدادات النوافذ المنبثقة.');
            }
        }

        // دالة طباعة الصور
        function printImage(fileUrl) {
            // إنشاء iframe مخفي لتحميل وطباعة الصورة
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.left = '-9999px';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.src = fileUrl;

            document.body.appendChild(iframe);

            // انتظار تحميل المحتوى ثم الطباعة مباشرة
            iframe.onload = function() {
                setTimeout(() => {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        // إزالة الـ iframe بعد الطباعة
                        setTimeout(() => {
                            if (document.body.contains(iframe)) {
                                document.body.removeChild(iframe);
                            }
                        }, 1000);
                    } catch (e) {
                        console.log('Image print failed:', e);
                        // في حالة فشل الـ iframe، استخدم النافذة المخفية
                        const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                        if (printWindow) {
                            printWindow.onload = function() {
                                printWindow.print();
                                printWindow.close();
                            };
                        }
                        if (document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }
                }, 500);
            };

            // في حالة فشل تحميل الـ iframe
            iframe.onerror = function() {
                console.log('Image iframe load failed');
                const printWindow = window.open(fileUrl, '_blank', 'width=1,height=1');
                if (printWindow) {
                    printWindow.onload = function() {
                        printWindow.print();
                        printWindow.close();
                    };
                }
                if (document.body.contains(iframe)) {
                    document.body.removeChild(iframe);
                }
            };
        }

        // Function to show file selection indicator with icon - محسنة للثبات
        function showFileSelected(input, indicatorId) {
            const indicator = document.getElementById(indicatorId);
            const fileName = document.getElementById(indicatorId.replace('fileSelected', 'fileName'));

            if (input.files.length > 0) {
                const file = input.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
                const fileInfo = {
                    name: file.name,
                    size: fileSize,
                    timestamp: Date.now(),
                    inputId: input.id
                };

                // حفظ معلومات الملف في localStorage فوراً
                localStorage.setItem('fileSelected_' + indicatorId, JSON.stringify(fileInfo));

                // إظهار المؤشر فوراً
                displayFileIndicator(indicatorId, fileInfo);

                // إضافة مراقب لإعادة الإظهار عند تحديث الصفحة
                setTimeout(() => {
                    restoreFileIndicators();
                }, 100);

                // إضافة مراقب إضافي في حالة تأخر Livewire
                setTimeout(() => {
                    if (document.getElementById(indicatorId)) {
                        displayFileIndicator(indicatorId, fileInfo);
                    }
                }, 500);

            } else {
                // إزالة معلومات الملف من localStorage عند عدم اختيار ملف
                localStorage.removeItem('fileSelected_' + indicatorId);
                if (indicator) {
                    indicator.style.display = 'none';
                }
            }
        }

        // دالة منفصلة لإظهار المؤشر
        function displayFileIndicator(indicatorId, fileInfo) {
            const indicator = document.getElementById(indicatorId);
            const fileName = document.getElementById(indicatorId.replace('fileSelected', 'fileName'));

            if (fileName && fileInfo) {
                fileName.textContent = fileInfo.name + ' (' + fileInfo.size + ' MB)';
            }

            if (indicator) {
                indicator.style.display = 'block';

                // Add animation effect only if not already visible
                if (indicator.style.opacity !== '1') {
                    indicator.style.opacity = '0';
                    setTimeout(() => {
                        indicator.style.transition = 'opacity 0.3s ease-in-out';
                        indicator.style.opacity = '1';
                    }, 50);
                }
            }
        }

        // دالة استعادة حالة الملفات المحفوظة - محسنة
        function restoreFileIndicators() {
            // البحث عن جميع مؤشرات الملفات
            const indicators = document.querySelectorAll('[id^=\"fileSelected\"]');

            indicators.forEach(indicator => {
                const indicatorId = indicator.id;

                // استرجاع معلومات الملف من localStorage
                const savedFileInfo = localStorage.getItem('fileSelected_' + indicatorId);

                if (savedFileInfo) {
                    try {
                        const fileInfo = JSON.parse(savedFileInfo);

                        // التحقق من أن المعلومات ليست قديمة (أقل من 10 دقائق)
                        const tenMinutes = 10 * 60 * 1000;
                        if (Date.now() - fileInfo.timestamp < tenMinutes) {
                            displayFileIndicator(indicatorId, fileInfo);
                        } else {
                            // إزالة المعلومات القديمة
                            localStorage.removeItem('fileSelected_' + indicatorId);
                        }
                    } catch (e) {
                        // إزالة البيانات التالفة
                        localStorage.removeItem('fileSelected_' + indicatorId);
                    }
                }
            });
        }        // دالة تنظيف مؤشرات الملفات عند إغلاق المودال
        function clearFileIndicators(modalType) {
            const indicators = document.querySelectorAll('[id*=\"fileSelected' + modalType + '\"]');
            indicators.forEach(indicator => {
                localStorage.removeItem('fileSelected_' + indicator.id);
                indicator.style.display = 'none';
            });
        }

        // Initialize flatpickr for search fields
        document.addEventListener('livewire:load', function () {
            // Initialize flatpickr for search date inputs
            const searchDateInputs = document.querySelectorAll('.flatpickr-input');
            searchDateInputs.forEach(function(input) {
                if (!input.classList.contains('flatpickr-initialized')) {
                    let config = {
                        dateFormat: 'Y-m-d',
                        locale: 'ar',
                        allowInput: true
                    };

                    // Different config for different date types
                    if (input.classList.contains('flatpickr-datetime')) {
                        config.enableTime = true;
                        config.dateFormat = 'Y-m-d H:i:S';
                        config.time_24hr = true;
                    } else if (input.classList.contains('flatpickr-month-year')) {
                        config.placeholder = 'التاريخ';
                        config.altInput = true;
                        config.allowInput = true;
                        config.dateFormat = 'Y-m';
                        config.altFormat = 'F Y';
                        config.yearSelectorType = 'input';
                        config.locale = {
                            months: {
                                shorthand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                                    'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                                ],
                                longhand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                                    'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                                ]
                            }
                        };
                        config.plugins = [
                            new monthSelectPlugin({
                                shorthand: true,
                                dateFormat: 'Y-m',
                                altFormat: 'F Y',
                                theme: 'light'
                            })
                        ];
                    }

                    const fp = flatpickr(input, config);
                    input.classList.add('flatpickr-initialized');

                    // Sync with Livewire for search fields
                    fp.config.onChange.push(function(selectedDates, dateStr, instance) {
                        // Update the input value and trigger Livewire update
                        input.value = dateStr;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }
            });

            // استعادة مؤشرات الملفات المرفوعة
            restoreFileIndicators();
        });

        // استعادة مؤشرات الملفات بعد تحديثات Livewire
        document.addEventListener('livewire:updated', function () {
            setTimeout(() => {
                restoreFileIndicators();
            }, 100);
        });

        // إضافة مراقب DOM للتأكد من ثبات الأيقونات
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldRestore = false;
                mutations.forEach(function(mutation) {
                    // التحقق من إضافة أو إزالة عقد تحتوي على file input
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        const hasFileInput = addedNodes.some(node => {
                            return node.nodeType === 1 &&
                                   (node.querySelector &&
                                    node.querySelector('[id*=\"fileSelected\"]'));
                        });
                        if (hasFileInput) {
                            shouldRestore = true;
                        }
                    }
                });

                if (shouldRestore) {
                    setTimeout(() => {
                        restoreFileIndicators();
                    }, 200);
                }
            });

            // مراقبة تغييرات في body
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    </script>
@endsection";

        File::put(base_path("resources/views/content/{$name}/index.blade.php"), $mainView);

        // Check if we have select fields and add Select2 script to index.blade.php
        $hasSelect2 = false;
        foreach ($fields as $field) {
            if ($field['type'] === 'select') {
                $hasSelect2 = true;
                break;
            }
        }

        if ($hasSelect2) {
            $select2Script = $this->generateSelect2ScriptForLivewire($lowerSingular);
            // Add Select2 script to index.blade.php instead of modals
            $mainView = str_replace('    </script>' . "\n" . '@endsection', $select2Script . "\n    </script>\n@endsection", $mainView);
            File::put(base_path("resources/views/content/{$name}/index.blade.php"), $mainView);
        }

        // Generate additional JavaScript for enhanced features (no onkeypress functions)
        $additionalJS = $this->generateAdditionalJS($fields, $lowerSingular, false); // false = no more JS functions
        if ($additionalJS) {
            // Add JavaScript at the end of page-script section
            $mainView = str_replace('    </script>' . "\n" . '@endsection', $additionalJS . "\n    </script>\n@endsection", $mainView);
            File::put(base_path("resources/views/content/{$name}/index.blade.php"), $mainView);
        }

        // Create Livewire view directly instead of using template
        $livewireView = $this->generateLivewireView($name, $fields, $arName);

        File::put(base_path("resources/views/livewire/{$kebabName}/{$kebabSingular}.blade.php"), $livewireView);
        $this->info("👁️ Created Views");
    }

    protected function generateLivewireView($name, $fields, $arName)
    {
        $singularName = Str::singular($name);
        $lowerSingular = strtolower($singularName);
        $lowerName = strtolower($name);

        // متغيرات جديدة لـ Livewire kebab-case
        $kebabName = Str::kebab($name);
        $kebabSingular = Str::kebab($singularName);
        $lowerName = strtolower($name);

        // Generate table headers and search inputs based on fields
        if (empty($fields)) {
            $tableHeaders = "<th class=\"text-center\">اسم ال{$arName}</th>";
            $searchInputs = "<th class=\"text-center\">
                                    <input type=\"text\" wire:model.debounce.300ms=\"search.{$lowerSingular}_name\"
                                        class=\"form-control text-center\" placeholder=\"اسم ال{$arName}\"
                                        wire:key=\"search_{$lowerSingular}_name\">
                                </th>";
            $tableData = "<td class=\"text-center\">{{\${$singularName}->{$lowerSingular}_name}}</td>";
        } else {
            $headerArray = [];
            $searchArray = [];
            $dataArray = [];

            foreach ($fields as $field) {
                $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
                $fieldType = $field['type'] ?? 'text';

                // إضافة الهيدر إذا كان الحقل يظهر في الجدول
                if ($field['show_in_table'] ?? true) {
                    $headerArray[] = "<th class=\"text-center\">{$arabicLabel}</th>";
                }

                // Generate search input based on field type - إضافة البحث إذا كان الحقل يظهر في البحث
                if (($field['searchable'] ?? true) && ($field['show_in_search'] ?? true)) {
                    $inputType = 'text';
                    $inputClasses = 'form-control text-center';

                    if ($fieldType === 'checkbox' || $fieldType === 'boolean') {
                        // استخدام النصوص المخصصة للحقل
                        $trueLabel = $field['checkbox_true_label'] ?? 'مفعل';
                        $falseLabel = $field['checkbox_false_label'] ?? 'غير مفعل';

                        $searchArray[] = "<th class=\"text-center\">
                                        <select wire:model.debounce.300ms=\"search.{$field['name']}\"
                                            class=\"form-select text-center\"
                                            wire:key=\"search_{$field['name']}\">
                                            <option value=\"\">جميع الحالات</option>
                                            <option value=\"1\">{$trueLabel}</option>
                                            <option value=\"0\">{$falseLabel}</option>
                                        </select>
                                    </th>";
                    } elseif ($fieldType === 'select') {
                        // For select fields, create a dropdown with options (same logic as in modals)
                        $optionsHtml = '<option value="">جميع الخيارات</option>';

                        // Handle select options based on source type (same logic as in modals)
                        if (($field['select_source'] ?? 'manual') === 'manual') {
                            // Manual options
                            if (!empty($field['select_options'])) {
                                foreach ($field['select_options'] as $option) {
                                    $optionsHtml .= "\n                                            <option value=\"{$option}\">{$option}</option>";
                                }
                            }
                        } else {
                            // Database options - will be handled in Livewire component
                            $relatedTable = $field['select_table'] ?? $field['related_table'] ?? '';
                            $relatedKey = $field['select_value'] ?? $field['related_key'] ?? 'id';
                            $relatedDisplay = $field['select_label'] ?? $field['related_display'] ?? '';

                            // Determine the correct field name for different tables
                            if (empty($relatedDisplay)) {
                                if ($relatedTable === 'departments') {
                                    $relatedDisplay = 'department_name';
                                } else {
                                    $relatedDisplay = 'name';
                                }
                            }

                            if (!empty($relatedTable)) {
                                // Fix model path for HMVC structure
                                $modelName = Str::studly(Str::singular($relatedTable));
                                $pluralModelName = Str::studly($relatedTable);

                                // Try plural first, then singular
                                $modelPath1 = "App\\Models\\{$pluralModelName}\\{$pluralModelName}";
                                $modelPath2 = "App\\Models\\{$modelName}\\{$modelName}";

                                // Use conditional check in Blade to handle both cases
                                $optionsHtml .= "\n                                            @if(class_exists('{$modelPath1}'))
                                            @foreach({$modelPath1}::all() as \$item)
                                                <option value=\"{{ \$item->{$relatedKey} }}\">{{ \$item->{$relatedDisplay} }}</option>
                                            @endforeach
                                        @elseif(class_exists('{$modelPath2}'))
                                            @foreach({$modelPath2}::all() as \$item)
                                                <option value=\"{{ \$item->{$relatedKey} }}\">{{ \$item->{$relatedDisplay} }}</option>
                                            @endforeach
                                        @endif";
                            }
                        }

                        $searchArray[] = "<th class=\"text-center\">
                                        <select wire:model.debounce.300ms=\"search.{$field['name']}\"
                                            class=\"form-select text-center\"
                                            wire:key=\"search_{$field['name']}\">
                                            {$optionsHtml}
                                        </select>
                                    </th>";
                    } elseif ($fieldType === 'file') {
                        // For file fields, add a text search for file names
                        $searchArray[] = "<th class=\"text-center\">
                                        <input type=\"text\" wire:model.debounce.300ms=\"search.{$field['name']}\"
                                            class=\"form-control text-center\" placeholder=\"اسم الملف\"
                                            wire:key=\"search_{$field['name']}\">
                                    </th>";
                    } else {
                        // For regular input types - determine the correct input type
                        if ($fieldType === 'time') {
                            $inputType = 'time'; // استخدام HTML5 time input
                            $inputClasses = 'form-control text-center';
                        } elseif ($fieldType === 'date' || $fieldType === 'datetime' || $fieldType === 'month_year') {
                            $inputType = 'text'; // استخدام text مع flatpickr
                            $inputClasses = 'form-control text-center flatpickr-input';

                            // Add specific classes for different date types
                            if ($fieldType === 'datetime') {
                                $inputClasses .= ' flatpickr-datetime';
                            } elseif ($fieldType === 'month_year') {
                                $inputClasses .= ' flatpickr-month-year';
                            } else {
                                $inputClasses .= ' flatpickr-date';
                            }
                        } elseif ($fieldType === 'email') {
                            $inputType = 'email';
                            $inputClasses = 'form-control text-center';
                        } elseif ($fieldType === 'number' || $fieldType === 'integer') {
                            $inputType = 'number';
                            $inputClasses = 'form-control text-center';
                        } else {
                            $inputType = 'text';
                            $inputClasses = 'form-control text-center';
                        }

                        // Add wire:ignore for flatpickr fields (except date calculation fields)
                        $wireIgnoreAttr = '';
                        $isUsedInDateCalculation = false;

                        // التحقق من استخدام الحقل في حسابات التاريخ
                        foreach ($fields as $checkField) {
                            if (($checkField['calculation_type'] ?? '') === 'date_diff') {
                                if (($checkField['date_from_field'] ?? '') === $field['name'] ||
                                    ($checkField['date_to_field'] ?? '') === $field['name']) {
                                    $isUsedInDateCalculation = true;
                                    break;
                                }
                            }
                        }

                        if (($fieldType === 'date' || $fieldType === 'datetime' || $fieldType === 'month_year') && !$isUsedInDateCalculation) {
                            $wireIgnoreAttr = ' wire:ignore';
                        }

                        // تخصيص placeholder للبحث حسب نوع الحقل
                        $searchPlaceholder = $arabicLabel;
                        if ($field['type'] === 'email') {
                            $searchPlaceholder = 'name@example.com';
                        } elseif ($field['type'] === 'integer' || $field['type'] === 'number') {
                            $searchPlaceholder = '123';
                        } elseif ($field['type'] === 'decimal') {
                            $searchPlaceholder = '123.45';
                        }

                        $searchArray[] = "<th class=\"text-center\">
                                        <input{$wireIgnoreAttr} type=\"{$inputType}\" wire:model.debounce.300ms=\"search.{$field['name']}\"
                                            class=\"{$inputClasses}\" placeholder=\"{$searchPlaceholder}\"
                                            wire:key=\"search_{$field['name']}\">
                                    </th>";
                    }
                } else {
                    // إضافة عمود فارغ إذا كان الحقل يظهر في الجدول لكن ليس قابل للبحث أو مخفي من البحث
                    if ($field['show_in_table'] ?? true) {
                        $searchArray[] = "<th></th>";
                    }
                }

                // Generate data display based on field type - عرض البيانات إذا كان الحقل يظهر في الجدول
                if ($field['show_in_table'] ?? true) {
                    if ($fieldType === 'date') {
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']} ? \\Carbon\\Carbon::parse(\${$singularName}->{$field['name']})->format('Y/m/d') : '-'}}</td>";
                    } elseif ($fieldType === 'datetime') {
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']} ? \\Carbon\\Carbon::parse(\${$singularName}->{$field['name']})->format('Y/m/d H:i') : '-'}}</td>";
                    } elseif ($fieldType === 'time') {
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']} ?? '-'}}</td>";
                    } elseif ($fieldType === 'month_year') {
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']} ?? '-'}}</td>";
                    } elseif ($fieldType === 'file') {
                        $dataArray[] = "<td class=\"text-center\">
                                        @if(\${$singularName}->{$field['name']})
                                            <div class=\"d-flex justify-content-center gap-1\">
                                                <a href=\"{{Storage::url('{$lowerName}/' . \${$singularName}->{$field['name']})}}\" target=\"_blank\" class=\"btn btn-sm btn-outline-primary\">
                                                    <i class=\"mdi mdi-file-outline me-1\"></i>عرض
                                                </a>
                                                <button onclick=\"printFile('{{Storage::url('{$lowerName}/' . \${$singularName}->{$field['name']})}}')\" class=\"btn btn-sm btn-outline-secondary\">
                                                    <i class=\"mdi mdi-printer me-1\"></i>طباعة
                                                </button>
                                            </div>
                                        @else
                                            <span class=\"text-muted\">لا يوجد ملف</span>
                                        @endif
                                    </td>";
                    } elseif ($fieldType === 'checkbox' || $fieldType === 'boolean') {
                        // استخدام النصوص المخصصة للحقل
                        $trueLabel = $field['checkbox_true_label'] ?? 'مفعل';
                        $falseLabel = $field['checkbox_false_label'] ?? 'غير مفعل';

                        $dataArray[] = "<td class=\"text-center\">
                                        <span class=\"badge {{ \${$singularName}->{$field['name']} ? 'bg-success' : 'bg-danger' }}\">
                                            {{ \${$singularName}->{$field['name']} ? '{$trueLabel}' : '{$falseLabel}' }}
                                        </span>
                                    </td>";
                    } elseif ($fieldType === 'select' && !empty($field['select_source']) && $field['select_source'] === 'database') {
                        // For database-backed select fields, show the related model name
                        $relatedTable = $field['select_table'] ?? $field['related_table'] ?? '';
                        $relatedDisplay = $field['select_label'] ?? $field['related_display'] ?? '';

                        // Determine the correct field name for different tables
                        if (empty($relatedDisplay)) {
                            if ($relatedTable === 'departments') {
                                $relatedDisplay = 'department_name';
                            } else {
                                $relatedDisplay = 'name';
                            }
                        }

                        if ($relatedTable) {
                            // Determine model class based on table name
                            if ($relatedTable === 'departments') {
                                $modelClass = "App\\Models\\Departments\\Departments";
                                $fallbackClass = "App\\Models\\Department\\Department";
                                $dataArray[] = "<td class=\"text-center\">
                                        @if(\${$singularName}->{$field['name']})
                                            @if(class_exists('{$modelClass}'))
                                                {{ {$modelClass}::find(\${$singularName}->{$field['name']})?->{$relatedDisplay} ?? 'غير محدد' }}
                                            @elseif(class_exists('{$fallbackClass}'))
                                                {{ {$fallbackClass}::find(\${$singularName}->{$field['name']})?->{$relatedDisplay} ?? 'غير محدد' }}
                                            @else
                                                {{ \${$singularName}->{$field['name']} }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>";
                            } else {
                                // Generic handling for other tables
                                $modelName = Str::studly(Str::singular($relatedTable));
                                $pluralModelName = Str::studly($relatedTable);
                                $modelClass1 = "App\\Models\\{$pluralModelName}\\{$pluralModelName}";
                                $modelClass2 = "App\\Models\\{$modelName}\\{$modelName}";

                                $dataArray[] = "<td class=\"text-center\">
                                        @if(\${$singularName}->{$field['name']})
                                            @if(class_exists('{$modelClass1}'))
                                                {{ {$modelClass1}::find(\${$singularName}->{$field['name']})?->{$relatedDisplay} ?? 'غير محدد' }}
                                            @elseif(class_exists('{$modelClass2}'))
                                                {{ {$modelClass2}::find(\${$singularName}->{$field['name']})?->{$relatedDisplay} ?? 'غير محدد' }}
                                            @else
                                                {{ \${$singularName}->{$field['name']} }}
                                            @endif
                                        @else
                                            غير محدد
                                        @endif
                                    </td>";
                            }
                        } else {
                            $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']}}}</td>";
                        }
                    } elseif ($fieldType === 'select') {
                        // For select fields, just display the value (or you can map to labels if needed)
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']}}}</td>";
                    } else {
                        // Default case - ensure ALL other fields are included
                        $dataArray[] = "<td class=\"text-center\">{{\${$singularName}->{$field['name']}}}</td>";
                    }
                } // إغلاق if ($field['show_in_table'] ?? true)
            }

            $tableHeaders = implode("\n                                ", $headerArray);
            $searchInputs = implode("\n                                ", $searchArray);
            $tableData = implode("\n                                    ", $dataArray);
        }

        // Generate the complete view with proper structure for Livewire 2.x
        $completeView = "<div class=\"mt-n4\">
    @can('{$lowerSingular}-view')
        <div class=\"card\">
            <div class=\"card-header\">
                <div class=\"d-flex justify-content-between\">
                    <div class=\"w-50\">
                        <nav aria-label=\"breadcrumb\">
                            <ol class=\"breadcrumb breadcrumb-style1 mb-0\">
                                <li class=\"breadcrumb-item fs-4\">
                                    <i class=\"mdi mdi-view-dashboard \"></i>
                                    <a href=\"{{ route('Dashboard') }}\">لوحة التحكم</a>
                                </li>
                                <li class=\"breadcrumb-item active fs-4\">
                                    <span class=\"fw-bold text-primary d-flex align-items-center\">
                                        <i class=\"mdi mdi-cog me-1 fs-4\"></i>
                                        <span class=\"ms-1\">{$arName}</span>
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <div class=\"d-flex gap-2\">
                            <!-- Unified Dropdown for Export/Print options -->
                            @if(auth()->user()->can('{$lowerSingular}-export-excel') || auth()->user()->can('{$lowerSingular}-export-pdf'))
                                <div class=\"btn-group\" role=\"group\">
                                    <button type=\"button\" class=\"btn btn-primary dropdown-toggle\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">
                                        <i class=\"mdi mdi-download me-1\"></i>
                                        تصدير / طباعة
                                    </button>
                                    <ul class=\"dropdown-menu\">
                                        @can('{$lowerSingular}-export-excel')
                                            <li>
                                                <a class=\"dropdown-item\" href=\"#\" wire:click=\"exportSelected\" {{ \$selectedRows && count(\$selectedRows) > 0 ? '' : 'onclick=\"return false;\"' }} style=\"{{ \$selectedRows && count(\$selectedRows) > 0 ? '' : 'opacity: 0.5; cursor: not-allowed;' }}\">
                                                    <i class=\"mdi mdi-file-excel me-2 text-success\"></i>
                                                    تصدير Excel
                                                </a>
                                            </li>
                                            <li><hr class=\"dropdown-divider\"></li>
                                        @endcan
                                        @can('{$lowerSingular}-export-pdf')
                                            <li>
                                                <a class=\"dropdown-item\" href=\"{{ route('{$name}.export.pdf.tcpdf') }}\">
                                                    <i class=\"mdi mdi-file-pdf-box me-2 text-danger\"></i>
                                                    تصدير PDF (TCPDF)
                                                </a>
                                            </li>
                                            <li>
                                                <a class=\"dropdown-item\" href=\"{{ route('{$name}.print.view') }}\" target=\"_blank\">
                                                    <i class=\"mdi mdi-printer me-2 text-info\"></i>
                                                    طباعة مباشرة
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            @endif
                            @can('{$lowerSingular}-create')
                                <button wire:click='Add{$singularName}ModalShow' class=\"mb-3 add-new btn btn-primary mb-md-0\"
                                    data-bs-toggle=\"modal\" data-bs-target=\"#add{$lowerSingular}Modal\">أضــافــة</button>
                            @endcan
                        </div>
                        @include('livewire.{$kebabName}.modals.add-{$kebabSingular}')
                    </div>
                </div>
            </div>
            @can('{$lowerSingular}-list')
                <div class=\"table-responsive\">
                    <table class=\"table\">
                        <thead class=\"table-light\">
                            <tr>
                                <th>
                                    <div class=\"form-check\">
                                        <input type=\"checkbox\" class=\"form-check-input\" wire:model=\"selectAll\" id=\"selectAll\">
                                    </div>
                                </th>
                                <th>#</th>
                                {$tableHeaders}
                                <th class=\"text-center\">العملية</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                {$searchInputs}
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                \$i = \$links->perPage() * (\$links->currentPage() - 1) + 1;
                            @endphp
                            @foreach (\${$name} as \${$singularName})
                                <tr>
                                    <td>
                                        <div class=\"form-check\">
                                            <input type=\"checkbox\" class=\"form-check-input\" wire:model=\"selectedRows\"
                                                value=\"{{ \${$singularName}->id }}\">
                                        </div>
                                    </td>
                                    <td>{{ \$i++ }}</td>
                                    {$tableData}
                                    <td class=\"text-center\">
                                        <div class=\"btn-group\" role=\"group\" aria-label=\"First group\">
                                            @can('{$lowerSingular}-edit')
                                                <button wire:click=\"Get{$singularName}({{\${$singularName}->id}})\"
                                                    class=\"p-0 px-1 btn btn-text-primary waves-effect\" data-bs-toggle=\"modal\"
                                                    data-bs-target=\"#edit{$lowerSingular}Modal\">
                                                    <i class=\"mdi mdi-text-box-edit-outline fs-3\"></i>
                                                </button>
                                            @endcan
                                            @can('{$lowerSingular}-delete')
                                                <strong style=\"margin: 0 10px;\">|</strong>
                                                <button wire:click=\"Get{$singularName}({{\${$singularName}->id}})\"
                                                    class=\"p-0 px-1 btn btn-text-danger waves-effect\"
                                                    data-bs-toggle = \"modal\" data-bs-target=\"#remove{$lowerSingular}Modal\">
                                                    <i class=\"tf-icons mdi mdi-delete-outline fs-3\"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class=\"mt-2 d-flex justify-content-center\">
                        {{ \$links->onEachSide(0)->links() }}
                    </div>
                </div>
                <!-- Modal -->
                @include('livewire.{$kebabName}.modals.edit-{$kebabSingular}')
                @include('livewire.{$kebabName}.modals.remove-{$kebabSingular}')
                <!-- Modal -->
            @endcan
        </div>
    @else
        <div class=\"container-xxl\">
            <div class=\"misc-wrapper\">
                <div class=\"card shadow-lg border-0\">
                    <div class=\"card-body text-center p-5\">
                        <div class=\"mb-4\">
                            <i class=\"mdi mdi-shield-lock-outline text-primary fs-1\" style=\"opacity: 0.9;\"></i>
                        </div>
                        <h2 class=\"mb-3 fw-semibold\">عذراً! ليس لديك صلاحيات الوصول</h2>
                        <p class=\"mb-4 mx-auto text-muted\" style=\"max-width: 500px;\">
                            لا تملك الصلاحيات الكافية للوصول إلى هذه الصفحة. يرجى التواصل مع مدير النظام للحصول على
                            المساعدة.
                        </p>
                        <a href=\"{{ route('Dashboard') }}\"
                            class=\"btn btn-primary btn-lg rounded-pill px-5 waves-effect waves-light\">
                            <i class=\"mdi mdi-home-outline me-1\"></i>
                            العودة إلى الرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>";

        // Add Select2 initialization for Livewire if needed
        $hasSelect2 = false;
        if (!empty($fields)) {
            foreach ($fields as $field) {
                if ($field['type'] === 'select') {
                    $hasSelect2 = true;
                    break;
                }
            }
        }

        return $completeView;
    }

    protected function createModals($name, $fields, $arName)
    {
        $singularName = Str::singular($name);
        $lowerSingular = strtolower($singularName);
        $lowerName = strtolower($name);

        // Generate form fields for modals
        if (empty($fields)) {
            $addFormFields = "<div class=\"row\">
                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model.defer='{$lowerSingular}_name' type=\"text\"
                                        id=\"modal{$singularName}{$lowerSingular}_name\" placeholder=\"اسم ال{$arName}\"
                                        class=\"form-control @error('{$lowerSingular}_name') is-invalid is-filled @enderror\"/>
                                    <label for=\"modal{$singularName}{$lowerSingular}_name\">اسم ال{$arName}</label>
                                </div>
                                @error('{$lowerSingular}_name')
                                    <small class='text-danger inputerror'> {{ \$message }} </small>
                                @enderror
                            </div>
                        </div>";

            $editFormFields = "<div class=\"row\">
                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model='{$lowerSingular}_name' type=\"text\"
                                        id=\"modalEdit{$singularName}{$lowerSingular}_name\" placeholder=\"اسم ال{$arName}\"
                                        class=\"form-control @error('{$lowerSingular}_name') is-invalid is-filled @enderror\" />
                                    <label for=\"modalEdit{$singularName}{$lowerSingular}_name\">اسم ال{$arName}</label>
                                </div>
                                @error('{$lowerSingular}_name')
                                    <small class='text-danger inputerror'> {{ \$message }} </small>
                                @enderror
                            </div>
                        </div>";
        } else {
            $addFieldArray = [];
            $editFieldArray = [];

            // Group fields in rows of 2
            $fieldChunks = array_chunk($fields, 2);

            foreach ($fieldChunks as $chunk) {
                $addRowFields = [];
                $editRowFields = [];

                foreach ($chunk as $field) {
                    // تحقق من إذا كان الحقل يجب أن يظهر في النماذج
                    if (!($field['show_in_forms'] ?? true)) {
                        continue; // تخطي الحقل إذا كان مخفي من النماذج
                    }

                    $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
                    $fieldType = $this->getInputType($field['type'] ?? 'text');
                    $colClass = count($chunk) === 2 ? 'col-md-6' : 'col';

                    // Special handling for select inputs
                    if (($field['type'] ?? 'text') === 'select') {
                        $options = '';

                        // Handle select options based on source type
                        if (($field['select_source'] ?? 'manual') === 'manual') {
                            // Manual options
                            if (!empty($field['select_options'])) {
                                foreach ($field['select_options'] as $option) {
                                    $options .= "<option value=\"{$option}\">{$option}</option>";
                                }
                            }
                        } else {
                            // Database options - will be handled in Livewire component
                            $relatedTable = $field['select_table'] ?? $field['related_table'] ?? '';
                            $relatedKey = $field['select_value'] ?? $field['related_key'] ?? 'id';
                            $relatedDisplay = $field['select_label'] ?? $field['related_display'] ?? '';

                            // Determine the correct field name for different tables
                            if (empty($relatedDisplay)) {
                                if ($relatedTable === 'departments') {
                                    $relatedDisplay = 'department_name';
                                } else {
                                    $relatedDisplay = 'name';
                                }
                            }

                            if (!empty($relatedTable)) {
                                // Fix model path for HMVC structure
                                $inputTable = $relatedTable; // Store original input
                                $modelName = Str::studly(Str::singular($relatedTable)); // Convert to singular StudlyCase
                                $pluralModelName = Str::studly($relatedTable); // Keep as plural StudlyCase

                                // Try plural first (common in our system), then singular
                                $modelPath1 = "App\\Models\\{$pluralModelName}\\{$pluralModelName}";
                                $modelPath2 = "App\\Models\\{$modelName}\\{$modelName}";

                                // Use conditional check in Blade to handle both cases
                                $options .= "@if(class_exists('{$modelPath1}'))
                                        @foreach({$modelPath1}::all() as \$item)
                                            <option value=\"{{ \$item->{$relatedKey} }}\">{{ \$item->{$relatedDisplay} }}</option>
                                        @endforeach
                                    @elseif(class_exists('{$modelPath2}'))
                                        @foreach({$modelPath2}::all() as \$item)
                                            <option value=\"{{ \$item->{$relatedKey} }}\">{{ \$item->{$relatedDisplay} }}</option>
                                        @endforeach
                                    @endif";
                            }
                        }

                        // Add Select2 class if enabled - removed since we'll use wire:ignore
                        $selectClass = 'form-select @error(\'' . $field['name'] . '\') is-invalid is-filled @enderror';

                        // Add modal field for select
                        $addRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-floating form-floating-outline\" wire:ignore>
                                        <select wire:model.defer='{$field['name']}'
                                            id=\"modal{$singularName}{$field['name']}\"
                                            class=\"{$selectClass}\">
                                            <option value=\"\">اختر {$arabicLabel}</option>
                                            {$options}
                                        </select>
                                    </div>
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";

                        // Edit modal field for select
                        $editRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-floating form-floating-outline\" wire:ignore>
                                        <select wire:model='{$field['name']}'
                                            id=\"modalEdit{$singularName}{$field['name']}\"
                                            class=\"{$selectClass}\">
                                            <option value=\"\">اختر {$arabicLabel}</option>
                                            {$options}
                                        </select>
                                    </div>
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";
                    }
                    // Special handling for checkbox inputs
                    elseif (($field['type'] ?? 'text') === 'checkbox' || ($field['type'] ?? 'text') === 'boolean') {
                        // Add modal field for checkbox
                        $addRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-check form-switch\">
                                        <input wire:model.defer='{$field['name']}' type=\"checkbox\"
                                            id=\"modal{$singularName}{$field['name']}\" value=\"1\"
                                            class=\"form-check-input @error('{$field['name']}') is-invalid @enderror\"/>
                                        <label class=\"form-check-label\" for=\"modal{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                    </div>
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";

                        // Edit modal field for checkbox
                        $editRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-check form-switch\">
                                        <input wire:model='{$field['name']}' type=\"checkbox\"
                                            id=\"modalEdit{$singularName}{$field['name']}\" value=\"1\"
                                            class=\"form-check-input @error('{$field['name']}') is-invalid @enderror\" />
                                        <label class=\"form-check-label\" for=\"modalEdit{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                    </div>
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";
                    }
                    // Special handling for file inputs
                    elseif (($field['type'] ?? 'text') === 'file') {
                        // Add modal field for file
                        $addRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-floating form-floating-outline\">
                                        <input wire:model.defer='{$field['name']}' type=\"file\" accept=\".jpeg,.png,.jpg,.pdf\"
                                            id=\"modal{$singularName}{$field['name']}\" placeholder=\"{$arabicLabel}\"
                                            class=\"form-control @error('{$field['name']}') is-invalid is-filled @enderror\"
                                            onchange=\"showFileSelected(this, 'fileSelected{$field['name']}')\"/>
                                        <label for=\"modal{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                    </div>
                                    <!-- File selection indicator -->
                                    <div id=\"fileSelected{$field['name']}\" class=\"mt-2\" style=\"display: none;\">
                                        <div class=\"alert alert-success py-2 px-3\">
                                            <small class=\"text-success d-flex align-items-center\">
                                                <i class=\"mdi mdi-check-circle me-2\" style=\"font-size: 1.1em;\"></i>
                                                <span>تم اختيار الملف: </span>
                                                <span class=\"fw-bold ms-1\" id=\"fileName{$field['name']}\"></span>
                                            </small>
                                        </div>
                                    </div>
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";

                        // Edit modal field for file
                        $editRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                    <div class=\"form-floating form-floating-outline\">
                                        <input wire:model.defer='{$field['name']}' type=\"file\" accept=\".jpeg,.png,.jpg,.pdf\"
                                            id=\"modalEdit{$singularName}{$field['name']}\" placeholder=\"{$arabicLabel}\"
                                            class=\"form-control @error('{$field['name']}') is-invalid is-filled @enderror\"
                                            onchange=\"showFileSelected(this, 'fileSelectedEdit{$field['name']}')\"/>
                                        <label for=\"modalEdit{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                    </div>
                                    <!-- File selection indicator for edit -->
                                    <div id=\"fileSelectedEdit{$field['name']}\" class=\"mt-2\" style=\"display: none;\">
                                        <div class=\"alert alert-success py-2 px-3\">
                                            <small class=\"text-success d-flex align-items-center\">
                                                <i class=\"mdi mdi-check-circle me-2\" style=\"font-size: 1.1em;\"></i>
                                                <span>تم اختيار الملف: </span>
                                                <span class=\"fw-bold ms-1\" id=\"fileNameEdit{$field['name']}\"></span>
                                            </small>
                                        </div>
                                    </div>
                                    @if(\$previewFile{$field['name']})
                                        <div class=\"mt-2\">
                                            <small class=\"text-muted\">الملف الحالي:</small>
                                            <a href=\"{{Storage::url('{$lowerName}/' . \$previewFile{$field['name']})}}\" target=\"_blank\" class=\"btn btn-sm btn-outline-info\">عرض الملف</a>
                                        </div>
                                    @endif
                                    @error('{$field['name']}')
                                        <small class='text-danger inputerror'> {{ \$message }} </small>
                                    @enderror
                                </div>";
                    } else {

                        // Add Flatpickr class for date fields (except time)
                        $inputClass = 'form-control @error(\'' . $field['name'] . '\') is-invalid is-filled @enderror';
                        if (in_array($field['type'], ['date', 'datetime', 'month_year']) && ($options['flatpickr'] ?? true)) {
                            $inputClass .= ' flatpickr-input';
                            // Add specific classes for different date types
                            if ($field['type'] === 'datetime') {
                                $inputClass .= ' flatpickr-datetime';
                            } elseif ($field['type'] === 'month_year') {
                                $inputClass .= ' flatpickr-month-year';
                            } else {
                                $inputClass .= ' flatpickr-date';
                            }
                        }

                        // إضافة readonly للحقول المحسوبة
                        $readonlyAttr = '';
                        if ($field['is_calculated'] ?? false) {
                            $readonlyAttr = ' readonly';
                            $inputClass .= ' bg-light text-muted';
                        }

                        $addAttrString = !empty($addAttributes) ? ' ' . implode(' ', $addAttributes) : '';
                        $addAttrString .= $readonlyAttr;

                        $editAttrString = !empty($editAttributes) ? ' ' . implode(' ', $editAttributes) : '';
                        $editAttrString .= $readonlyAttr;

        // Add wire:ignore for flatpickr fields (except time and date calculation fields)
        $wireIgnore = '';
        $isUsedInDateCalculation = false;
        $isUsedInTimeCalculation = false;

        // التحقق من استخدام الحقل في حسابات التاريخ
        foreach ($fields as $checkField) {
            if (($checkField['calculation_type'] ?? '') === 'date_diff') {
                if (($checkField['date_from_field'] ?? '') === $field['name'] ||
                    ($checkField['date_to_field'] ?? '') === $field['name']) {
                    $isUsedInDateCalculation = true;
                    break;
                }
            }
        }

        // التحقق من استخدام الحقل في حسابات الوقت
        foreach ($fields as $checkField) {
            if (($checkField['calculation_type'] ?? '') === 'time_diff') {
                if (($checkField['time_from_field'] ?? '') === $field['name'] ||
                    ($checkField['time_to_field'] ?? '') === $field['name']) {
                    $isUsedInTimeCalculation = true;
                    break;
                }
            }
        }                        if (in_array($field['type'], ['date', 'datetime', 'month_year']) && !$isUsedInDateCalculation) {
                            $wireIgnore = ' wire:ignore';
                        }
                        if ($field['type'] === 'time' && !$isUsedInTimeCalculation) {
                            $wireIgnore = ' wire:ignore';
                        }

                        // إضافة استدعاء دالة العمليات الحسابية للحقول الرقمية
                        $wireChange = '';
                        // التحقق من وجود حقول محسوبة في المشروع
                        $hasCalculatedFields = false;
                        foreach ($fields as $checkField) {
                            if ($checkField['is_calculated'] ?? false) {
                                $hasCalculatedFields = true;
                                break;
                            }
                        }
                        if ($hasCalculatedFields && in_array($field['type'], ['integer', 'decimal'])) {
                            $wireChange = ' wire:input="calculateFields()"';
                        }

        // تحديد wire:model للحساب الفوري
        $wireModelType = 'wire:model.defer'; // الافتراضي

        // للحقول المحسوبة أو المستخدمة في الحسابات
        if ($hasCalculatedFields) {
            // للحقول الرقمية المحسوبة
            if (in_array($field['type'], ['integer', 'decimal']) && !in_array($field['type'], ['select', 'checkbox', 'file'])) {
                $wireModelType = 'wire:model';
            }
            // للحقول المستخدمة في حسابات التاريخ أو الوقت
            elseif ($isUsedInDateCalculation || $isUsedInTimeCalculation) {
                $wireModelType = 'wire:model';
            }
        }                        // إضافة JavaScript callback للحقول المستخدمة في حسابات التاريخ
                        $onChangeCallback = '';
                        if ($isUsedInDateCalculation && in_array($field['type'], ['date', 'datetime', 'month_year'])) {
                            $onChangeCallback = ' onchange="@this.set(\'' . $field['name'] . '\', this.value); @this.call(\'calculateFields\')"';
                        }
                        if ($isUsedInTimeCalculation && in_array($field['type'], ['time', 'datetime'])) {
                            $onChangeCallback = ' onchange="@this.set(\'' . $field['name'] . '\', this.value); @this.call(\'calculateFields\')"';
                        }

                        // تخصيص placeholder حسب نوع الحقل
                        $placeholder = $arabicLabel;
                        if ($field['type'] === 'email') {
                            $placeholder = 'name@example.com';
                        } elseif ($field['type'] === 'integer' || $field['type'] === 'number') {
                            $placeholder = '123';
                        } elseif ($field['type'] === 'decimal') {
                            $placeholder = '123.45';
                        }

                        // معالجة خاصة لحقول النص الطويل
                        if ($field['type'] === 'text') {
                            // Add modal field for textarea
                            $addRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <textarea wire:model.defer='{$field['name']}'
                                                id=\"modal{$singularName}{$field['name']}\" placeholder=\"{$placeholder}\"
                                                class=\"form-control h-px-100 @error('{$field['name']}') is-invalid is-filled @enderror\"></textarea>
                                            <label for=\"modal{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                        </div>
                                        @error('{$field['name']}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";

                            // Edit modal field for textarea
                            $editRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <textarea wire:model='{$field['name']}'
                                                id=\"modalEdit{$singularName}{$field['name']}\" placeholder=\"{$placeholder}\"
                                                class=\"form-control h-px-100 @error('{$field['name']}') is-invalid is-filled @enderror\"></textarea>
                                            <label for=\"modalEdit{$singularName}{$field['name']}\">{$arabicLabel}</label>
                                        </div>
                                        @error('{$field['name']}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";
                        } else {
                            // Add modal field for regular input
                            $addRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <input{$wireIgnore} {$wireModelType}='{$field['name']}'{$wireChange} type=\"{$fieldType}\"
                                                id=\"modal{$singularName}{$field['name']}\" placeholder=\"{$placeholder}\"
                                                class=\"{$inputClass}\"{$addAttrString}{$onChangeCallback}/>
                                            <label for=\"modal{$singularName}{$field['name']}\">{$arabicLabel}" .
                                            (($field['is_calculated'] ?? false) ? " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>" : "") .
                                            "</label>
                                        </div>
                                        @error('{$field['name']}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";

                            // Edit modal field for regular input
                            $editRowFields[] = "<div class=\"mb-3 {$colClass}\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <input{$wireIgnore} {$wireModelType}='{$field['name']}'{$wireChange} type=\"{$fieldType}\"
                                                id=\"modalEdit{$singularName}{$field['name']}\" placeholder=\"{$placeholder}\"
                                                class=\"{$inputClass}\"{$editAttrString}{$onChangeCallback} />
                                            <label for=\"modalEdit{$singularName}{$field['name']}\">{$arabicLabel}" .
                                            (($field['is_calculated'] ?? false) ? " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>" : "") .
                                            "</label>
                                        </div>
                                        @error('{$field['name']}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";
                        }
                    }
                }

                $addFieldArray[] = "<div class=\"row\">\n                            " . implode("\n                            ", $addRowFields) . "\n                        </div>";
                $editFieldArray[] = "<div class=\"row\">\n                            " . implode("\n                            ", $editRowFields) . "\n                        </div>";
            }

            $addFormFields = implode("\n                        ", $addFieldArray);
            $editFormFields = implode("\n                        ", $editFieldArray);
        }

        // Create Add Modal
        $this->createModal('add', $name, $arName, $addFormFields);

        // Create Edit Modal
        $this->createModal('edit', $name, $arName, $editFormFields);

        // Create Remove Modal
        $this->createModal('remove', $name, $arName, '');

        $this->info("🎭 Created Modals");
    }

    protected function getInputType($fieldType)
    {
        switch ($fieldType) {
            case 'email':
                return 'email';
            case 'date':
                return 'date';
            case 'datetime':
                return 'text'; // نستخدم text مع flatpickr للتاريخ والوقت
            case 'time':
                return 'time'; // نستخدم HTML5 time input
            case 'month_year':
                return 'text'; // نستخدم text مع flatpickr لاختيار الشهر والسنة
            case 'number':
            case 'integer':
                return 'number';
            case 'decimal':
                return 'text'; // نستخدم text للحقول العشرية حتى نتمكن من التحقق المخصص
            case 'password':
                return 'password';
            case 'file':
                return 'file';
            default:
                return 'text';
        }
    }

    protected function createModal($type, $name, $arName, $formFields)
    {
        $singularName = Str::singular($name);
        $lowerSingular = strtolower($singularName);
        $lowerName = strtolower($name);

        // متغيرات kebab-case
        $kebabName = Str::kebab($name);
        $kebabSingular = Str::kebab($singularName);

        if ($type === 'add') {
            $modalContent = "<!-- Add {$singularName} Modal -->
<div wire:ignore.self class=\"modal fade\" id=\"add{$lowerSingular}Modal\" tabindex=\"-1\" aria-hidden=\"true\">
    <div class=\"modal-dialog modal-dialog-centered modal-lg\">
        <div class=\"p-4 modal-content p-md-5\">
            <button type=\"button\" class=\"btn-close btn-pinned\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>
            <div class=\"modal-body p-md-0\">
                <div class=\"mb-4 text-center mt-n4\">
                    <div class=\"text-center mb-4\">
                        <h3 class=\"fw-bold mb-2\">
                            <span class=\"text-primary\">اضافة</span> {$arName} جديد
                        </h3>
                        <p class=\"text-muted\">
                            <i class=\"mdi mdi-cog me-1\"></i>
                            قم بإدخال تفاصيل {$arName} في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class=\"mt-n2\">
                <div wire:loading.remove wire:target=\"store, Get{$singularName}\">
                    <form id=\"add{$lowerSingular}ModalForm\" autocomplete=\"off\">
                        {$formFields}
                        <hr class=\"my-0\">
                        <div class=\"text-center col-12 demo-vertical-spacing mb-n4\">
                            <button wire:click='store' wire:loading.attr=\"disabled\" type=\"button\"
                                class=\"btn btn-primary me-sm-3 me-1\">اضافة</button>
                            <button type=\"reset\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\"
                                aria-label=\"Close\">تجاهل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Add {$singularName} Modal -->
";
        } elseif ($type === 'edit') {
            $modalContent = "<!-- Edite {$singularName} Modal -->
<div wire:ignore.self class=\"modal fade\" id=\"edit{$lowerSingular}Modal\" tabindex=\"-1\" aria-hidden=\"true\">
    <div class=\"modal-dialog modal-dialog-centered modal-lg\">
        <div class=\"p-4 modal-content p-md-5\">
            <button type=\"button\" class=\"btn-close btn-pinned\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>
            <div class=\"modal-body p-md-0\">
                <div class=\"mb-4 text-center mt-n4\">
                    <div class=\"text-center mb-4\">
                        <h3 class=\"fw-bold mb-2\">
                            <span class=\"text-warning\">تعديل</span> بيانات {$arName}
                        </h3>
                        <p class=\"text-muted\">
                            <i class=\"mdi mdi-cog me-1\"></i>
                            قم بتعديل تفاصيل {$arName} في النموذج أدناه
                        </p>
                    </div>
                </div>
                <hr class=\"mt-n2\">
                <div wire:loading.remove wire:target=\"update, Get{$singularName}\">
                    <form id=\"edit{$singularName}ModalForm\" autocomplete=\"off\">
                        {$formFields}
                        <hr class=\"my-0\">
                        <div class=\"text-center col-12 demo-vertical-spacing mb-n4\">
                            <button wire:click='update' wire:loading.attr=\"disabled\" type=\"button\"
                                class=\"btn btn-warning me-sm-3 me-1\">تعديل</button>
                            <button type=\"reset\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\"
                                aria-label=\"Close\">تجاهل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Edite {$singularName} Modal -->
";
        } else {
            $modalContent = "<!-- Remove {$singularName} Modal -->
<div wire:ignore.self class=\"modal fade\" id=\"remove{$lowerSingular}Modal\" tabindex=\"-1\" aria-hidden=\"true\">
    <div class=\"modal-dialog modal-dialog-centered\">
        <div class=\"p-4 modal-content p-md-5\">
            <button type=\"button\" class=\"btn-close btn-pinned\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>
            <div class=\"modal-body p-md-0\">
                <div class=\"mb-4 text-center mt-n4\">
                    <div class=\"mb-4 text-center\">
                        <i class=\"mdi mdi-trash-can-outline mdi-72px text-danger mb-4\"></i>
                        <h4 class=\"mb-2\">هل أنت متأكد؟</h4>
                        <p class=\"text-muted mx-4 mb-0\">لن تتمكن من التراجع عن هذا!</p>
                    </div>
                </div>
                <hr class=\"mt-n2\">
                <div wire:loading.remove wire:target=\"destroy, Get{$singularName}\">
                    <div class=\"text-center col-12 demo-vertical-spacing mb-n4\">
                        <button wire:click='destroy' type=\"button\" class=\"btn btn-danger me-sm-3 me-1\"
                            wire:loading.attr=\"disabled\">نعم, احذف!</button>
                        <button type=\"reset\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\"
                            aria-label=\"Close\">تجاهل</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Remove {$singularName} Modal -->";
        }

        $modalPath = base_path("resources/views/livewire/{$kebabName}/modals/{$type}-{$kebabSingular}.blade.php");
        File::put($modalPath, $modalContent);
    }

    protected function createMigration($name, $fields, $arName)
    {
        $tableName = Str::snake(Str::plural($name));
        $timestamp = date('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";

        // التحقق من وجود الجدول في قاعدة البيانات
        $tableExists = false;
        try {
            $tableExists = DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            // في حالة عدم وجود قاعدة البيانات أو خطأ الاتصال، اعتبر أن الجدول غير موجود
            $tableExists = false;
        }

        // البحث عن migration files موجودة للجدول نفسه وحذفها
        $migrationsPath = database_path('migrations');
        $existingMigrations = glob($migrationsPath . "/*_create_{$tableName}_table.php");
        $deletedOldMigrations = false;

        if (!empty($existingMigrations)) {
            $this->info("🗑️ تم العثور على " . count($existingMigrations) . " migration موجودة للجدول {$tableName}");
            foreach ($existingMigrations as $existingMigration) {
                $filename = basename($existingMigration);
                // إنشاء نسخة احتياطية قبل الحذف
                $backupFile = $existingMigration . '.backup.' . date('Y_m_d_H_i_s');
                copy($existingMigration, $backupFile);

                // حذف الملف القديم
                unlink($existingMigration);
                $this->info("✅ تم حذف: {$filename} (نسخة احتياطية: " . basename($backupFile) . ")");
                $deletedOldMigrations = true;
            }
        }

        if ($tableExists) {
            $this->info("⚠️ الجدول {$tableName} موجود بالفعل - سيتم حذفه وإعادة إنشاؤه");
        }

        // Generate migration fields
        if (empty($fields)) {
            $singularName = Str::singular($name);
            $fieldName = strtolower($singularName) . '_name';
            $migrationFields = "\$table->string('{$fieldName}')->comment('اسم ال{$arName}');";
        } else {
            $fieldArray = [];
            foreach ($fields as $field) {
                $fieldType = $field['type'] ?? 'string';

                // Convert common field types to Laravel migration types
                switch ($fieldType) {
                    case 'varchar':
                        $fieldType = 'string';
                        break;
                    case 'email':
                        $fieldType = 'string';
                        break;
                    case 'text':
                        // If text has size specified, convert to string, otherwise keep as text
                        if (isset($field['size']) && is_numeric($field['size'])) {
                            $fieldType = 'string';
                        } else {
                            $fieldType = 'text';
                        }
                        break;
                    case 'integer':
                    case 'numeric':
                    case 'number':
                    case 'select_numeric':
                    case 'calculated':
                        // تحديد نوع الرقم الصحيح بناءً على إعدادات الحقل
                        $integerType = $field['integer_type'] ?? 'bigint';
                        switch ($integerType) {
                            case 'tinyint':
                                $fieldType = 'tinyInteger';
                                break;
                            case 'smallint':
                                $fieldType = 'smallInteger';
                                break;
                            case 'int':
                                $fieldType = 'integer';
                                break;
                            case 'bigint':
                            default:
                                $fieldType = 'bigInteger';
                                break;
                        }
                        break;
                    case 'decimal':
                        $fieldType = 'decimal';
                        break;
                    case 'date':
                        $fieldType = 'date';
                        break;
                    case 'datetime':
                        $fieldType = 'dateTime';
                        break;
                    case 'time':
                        $fieldType = 'time';
                        break;
                    case 'month_year':
                        $fieldType = 'string'; // Store as string in format 'MM.YYYY'
                        break;
                    case 'checkbox':
                    case 'boolean':
                        $fieldType = 'boolean';
                        break;
                    case 'file':
                        $fieldType = 'string'; // Store file path as string
                        break;
                    default:
                        $fieldType = 'string';
                }

                // Handle size parameter for string and decimal types
                $sizeParam = '';
                if (isset($field['size']) && !empty($field['size'])) {
                    if ($fieldType === 'string') {
                        $sizeParam = ", {$field['size']}";
                    } elseif ($fieldType === 'decimal') {
                        $sizeParts = explode(',', $field['size']);
                        $precision = $sizeParts[0] ?? 15; // Default to 15 for billions support
                        $scale = $sizeParts[1] ?? 2;
                        $sizeParam = ", {$precision}, {$scale}";
                    }
                } elseif ($fieldType === 'decimal') {
                    // استخدام إعدادات decimal المخصصة
                    $precision = $field['decimal_precision'] ?? 15;
                    $scale = $field['decimal_scale'] ?? 2;
                    $sizeParam = ", {$precision}, {$scale}";
                }

                // For boolean fields, handle default values
                $defaultValue = '';
                if ($fieldType === 'boolean') {
                    $defaultValue = '->default(false)';
                }

                $nullable = ($field['required'] ?? true) ? '' : '->nullable()';
                $unique = ($field['unique'] ?? false) ? '->unique()' : '';
                $unsigned = '';

                // إضافة خصائص الأرقام الصحيحة
                if (in_array($field['type'], ['integer', 'numeric', 'number'])) {
                    $unsigned = ($field['unsigned'] ?? false) ? '->unsigned()' : '';
                }

                $arabicComment = $field['ar_name'] ?? $field['arabic_label'] ?? '';
                $commentSuffix = $arabicComment ? "->comment('{$arabicComment}')" : '';
                $fieldArray[] = "\$table->{$fieldType}('{$field['name']}'{$sizeParam}){$defaultValue}{$unsigned}{$nullable}{$unique}{$commentSuffix};";
            }
            $migrationFields = implode("\n            ", $fieldArray);
        }

        $content = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up()
    {" . ($tableExists ? "
        // حذف الجدول الموجود إذا كان موجوداً
        Schema::dropIfExists('{$tableName}');

        // إنشاء الجدول من جديد مع جميع الحقول
        " : "
        ") . "Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('user_id')->comment('معرف المستخدم');
            {$migrationFields}
            \$table->timestamps();

            \$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('{$tableName}');
    }
};";

        $path = base_path("database/migrations/{$timestamp}_{$migrationName}.php");
        File::put($path, $content);

        if ($tableExists || $deletedOldMigrations) {
            $this->info("🗄️ تم إنشاء Migration جديدة مع حذف الملفات القديمة وإعادة إنشاء الجدول");
        } else {
            $this->info("🗄️ تم إنشاء Migration جديدة");
        }
    }

    protected function addRoutes($name, $arName)
    {
        $singularName = Str::singular($name);
        $webRoutePath = base_path('routes/web.php');
        $currentContent = File::get($webRoutePath);

        // فحص إذا كان الـ Route موجود بالفعل
        $routeName = "->name('{$name}')";
        $routePattern = "Route::GET('{$name}'";

        if (str_contains($currentContent, $routeName) || str_contains($currentContent, $routePattern)) {
            $this->info("🛣️ Route for {$name} already exists - skipping");
            return;
        }

        // إضافة use statement إذا لم يكن موجوداً
        $this->addUseStatement($name, $singularName);

        // إعادة قراءة المحتوى بعد إضافة use statements
        $currentContent = File::get($webRoutePath);

        // البحث عن أفضل مكان لإدراج الـ routes (قبل آخر قوس إغلاق)
        $routeContent = "
    Route::GET('{$name}', [{$singularName}Controller::class, 'index'])->name('{$name}');
    Route::GET('{$name}/export-pdf-tcpdf', [{$singularName}TcpdfExportController::class, 'exportPdf'])->name('{$name}.export.pdf.tcpdf');
    Route::GET('{$name}/print-view', [{$singularName}PrintController::class, 'printView'])->name('{$name}.print.view');
";

        // البحث عن آخر قوس إغلاق }) في الملف لإدراج الـ routes قبلها
        $lastClosingBrace = strrpos($currentContent, '});');
        if ($lastClosingBrace !== false) {
            // إدراج الـ routes قبل آخر قوس إغلاق
            $beforeBrace = substr($currentContent, 0, $lastClosingBrace);
            $afterBrace = substr($currentContent, $lastClosingBrace);
            $newContent = $beforeBrace . $routeContent . $afterBrace;
            File::put($webRoutePath, $newContent);
        } else {
            // fallback: إضافة في النهاية إذا لم نجد قوس إغلاق
            File::append($webRoutePath, $routeContent);
        }
        $this->info("🛣️ Added Routes");
    }

    protected function addUseStatement($name, $singularName)
    {
        $webRoutePath = base_path('routes/web.php');
        $content = File::get($webRoutePath);

        $useStatements = [
            "use App\\Http\\Controllers\\{$name}\\{$singularName}Controller;",
            "use App\\Http\\Controllers\\{$name}\\{$singularName}TcpdfExportController;",
            "use App\\Http\\Controllers\\{$name}\\{$singularName}PrintController;"
        ];

        // فحص الـ use statements الموجودة والمطلوب إضافتها
        $statementsToAdd = [];
        foreach ($useStatements as $useStatement) {
            if (!str_contains($content, $useStatement)) {
                $statementsToAdd[] = $useStatement;
            }
        }

        if (empty($statementsToAdd)) {
            $this->info("📝 All use statements already exist for {$singularName}");
            return;
        }

        $lines = explode("\n", $content);
        $insertIndex = -1;

        // البحث عن آخر use statement
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (str_starts_with(trim($lines[$i]), 'use ') && str_ends_with(trim($lines[$i]), ';')) {
                $insertIndex = $i;
                break;
            }
        }

        if ($insertIndex === -1) {
            $this->error("❌ Could not find insertion point for use statements");
            return;
        }

        // إضافة الـ use statements الجديدة
        $addedCount = 0;
        foreach ($statementsToAdd as $useStatement) {
            array_splice($lines, $insertIndex + 1 + $addedCount, 0, $useStatement);
            $addedCount++;
        }

        $newContent = implode("\n", $lines);

        if (File::put($webRoutePath, $newContent)) {
            $this->info("📝 Added {$addedCount} use statement(s) for {$singularName} controllers");
        } else {
            $this->error("❌ Failed to write to web.php file");
        }
    }

    protected function addToNavigation($name, $arName, $moduleType = 'sub', $parentGroup = null)
    {
        // قراءة الأيقونة والترتيب المخصص من الأوبشن
        $itemIcon = $this->option('item-icon');
        $groupOrder = $this->option('group-order');

        try {
            if ($moduleType === 'main') {
                // إنشاء مجموعة أساسية للوحدة الرئيسية مع الأيقونة والترتيب المخصص
                $basicGroupId = $this->createBasicGroupForMainModule($name, $arName, $itemIcon, $groupOrder);

                // مولد الوحدات ينشئ item مستقل مع basic_group_id
                \App\Helpers\DynamicMenuHelper::addMenuItem('item', $name, $arName, $name, $this->getModuleIcon($name, $itemIcon), $name);

                // إضافة basic_group_id للوحدة الرئيسية في dynamic-menu
                $this->addBasicGroupIdToMenuItem($name, $basicGroupId);

                $this->info("🧭 Added to Dynamic Menu system");
                $this->info("✅ Main module added as standalone item with basic group");

                // إنهاء الدالة هنا
                return;
            }

            // إضافة كوحدة فرعية فقط إذا لم يكن main
            if ($parentGroup === 'project') {
                // استخدام الطريقة المخصصة للمشروع
                \App\Helpers\DynamicMenuHelper::addMenuItemToProject($name, $arName, $this->getModuleIcon($name, $itemIcon));
                $this->info("🧭 Added to Dynamic Menu system");
                $this->info("✅ Module added to 'المشروع' group");
            } elseif ($parentGroup === 'standalone') {
                // هذه وحدة أب، لا نضيفها للقائمة - ستتم إضافتها عبر DynamicMenuService
                $this->info("🧭 Standalone parent module - will be added via DynamicMenuService");
                $this->info("✅ Module configured as standalone parent");
            } else {
                // إضافة لمجموعة محددة
                \App\Helpers\DynamicMenuHelper::addMenuItemToGroup($parentGroup, $name, $arName, $this->getModuleIcon($name, $itemIcon));
                $this->info("🧭 Added to Dynamic Menu system");
                $this->info("✅ Module added to '{$parentGroup}' group");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error adding to navigation: " . $e->getMessage());
            // الطريقة القديمة كبديل
            $this->addToNavigationOld($name, $arName);
        }
    }

    protected function addToNavigationOld($name, $arName)
    {
        // لا نحتاج لإضافة أي شيء في contentNavbarLayout.blade.php
        // لأن النظام الديناميكي يتولى ذلك تلقائياً
        $this->info("🧭 Skipping old navigation method - using dynamic menu system instead");

        // تحقق من وجود الوحدة في dynamic-menu
        $dynamicMenuPath = config_path('dynamic-menu.php');
        if (File::exists($dynamicMenuPath)) {
            $menuConfig = require $dynamicMenuPath;
            $found = false;

            // البحث في جميع المجموعات والعناصر
            foreach ($menuConfig['menu_items'] as $group) {
                if (isset($group['children'])) {
                    foreach ($group['children'] as $child) {
                        if ($child['route'] === $name) {
                            $found = true;
                            break 2;
                        }
                    }
                }
            }

            if ($found) {
                $this->info("✅ Module '{$name}' already exists in dynamic menu");
            } else {
                $this->warn("⚠️ Module '{$name}' not found in dynamic menu - may need manual addition");
            }
        }

        return;
    }

    protected function showNavigationCode($name, $arName)
    {
        $iconClass = 'settings'; // Default icon, you can customize this

        $menuCode = "
<li class='menu-item'>
    <a href='{{route('{$name}')}}' class='menu-link'>
        <i class='menu-icon tf-icons bx bx-{$iconClass}'></i>
        <div>{$arName}</div>
    </a>
</li>";

        $this->info("\n📋 Navigation Integration Code:");
        $this->info("=================================");
        $this->info("Add this to your navigation menu:");
        $this->info($menuCode);

        $this->info("\n📋 Breadcrumb Code for contentNavbarLayout.blade.php:");
        $this->info("=================================");
        $breadcrumbCode = "
@elseif(request()->routeIs('{$name}'))
    <li class='breadcrumb-item'>
        <a href='javascript:void(0);'>{$arName}</a>
    </li>";

        $this->info($breadcrumbCode);
    }

    protected function createPermissions($name, $arName, $moduleType = 'sub', $parentGroup = null)
    {
        try {
            $lowerName = strtolower($name);
            $singularLowerName = strtolower(Str::singular($name));

            // Define permissions including main module permission
            $permissions = [
                [
                    'name' => $name, // الصلاحية الرئيسية للوحدة
                    'explain_name' => "{$arName} - الصلاحية الرئيسية"
                ],
                [
                    'name' => "{$singularLowerName}-view",
                    'explain_name' => "{$arName} - عرض"
                ],
                [
                    'name' => "{$singularLowerName}-create",
                    'explain_name' => "{$arName} - اضافة"
                ],
                [
                    'name' => "{$singularLowerName}-list",
                    'explain_name' => "{$arName} - بيانات"
                ],
                [
                    'name' => "{$singularLowerName}-edit",
                    'explain_name' => "{$arName} - تعديل"
                ],
                [
                    'name' => "{$singularLowerName}-delete",
                    'explain_name' => "{$arName} - حذف"
                ],
                [
                    'name' => "{$singularLowerName}-export-excel",
                    'explain_name' => "{$arName} - تصدير Excel"
                ],
                [
                    'name' => "{$singularLowerName}-export-pdf",
                    'explain_name' => "{$arName} - طباعة PDF"
                ]
            ];

            // If this is a main module, add group permission
            if ($moduleType === 'main') {
                $groupPermission = [
                    'name' => strtolower($name),
                    'explain_name' => "{$arName} - صلاحية المجموعة الرئيسية"
                ];

                // Add group permission at the beginning
                array_unshift($permissions, $groupPermission);
            }

            // Insert permissions into database
            foreach ($permissions as $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permission['name']],
                    [
                        'name' => $permission['name'],
                        'explain_name' => $permission['explain_name'],
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            // إعطاء جميع الصلاحيات لدور OWNER تلقائياً
            $ownerRole = DB::table('roles')->where('name', 'OWNER')->first();
            if ($ownerRole) {
                foreach ($permissions as $permission) {
                    // التحقق من عدم وجود العلاقة مسبقاً
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $ownerRole->id)
                        ->where('permission_id', function ($query) use ($permission) {
                            $query->select('id')
                                ->from('permissions')
                                ->where('name', $permission['name'])
                                ->limit(1);
                        })
                        ->exists();

                    if (!$exists) {
                        $permissionId = DB::table('permissions')->where('name', $permission['name'])->value('id');
                        if ($permissionId) {
                            DB::table('role_has_permissions')->insert([
                                'permission_id' => $permissionId,
                                'role_id' => $ownerRole->id
                            ]);
                        }
                    }
                }
            }

            $this->info("🔐 Created permissions for {$arName}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to create permissions: " . $e->getMessage());
        }
    }

    protected function generateAdditionalJS($fields, $lowerSingular = 'add', $includeSelect2 = true)
    {
        if (empty($fields)) return '';

        $jsCode = '';

        // Add validation functions for different field types
        $hasNumbersOnly = false;
        $hasArabicOnly = false;
        $hasFlatpickr = false;
        $hasSelect2 = false;

        foreach ($fields as $field) {
            if (($field['numeric_only'] ?? false) && in_array($field['type'], ['string', 'integer'])) {
                $hasNumbersOnly = true;
            }
            if (($field['arabic_only'] ?? false) && $field['type'] === 'string') {
                $hasArabicOnly = true;
            }
            if (in_array($field['type'], ['date', 'datetime', 'time', 'month_year'])) {
                $hasFlatpickr = true;
            }
            if ($field['type'] === 'select' && $includeSelect2) {
                $hasSelect2 = true;
            }
        }

        // Add Flatpickr initialization
        if ($hasFlatpickr) {
            $jsCode .= "
        // Initialize Flatpickr for date fields
        document.addEventListener('DOMContentLoaded', function() {
            initializeFlatpickr();
        });

        // Re-initialize Flatpickr after Livewire updates - Livewire v2 syntax
        document.addEventListener('livewire:load', function () {
            window.livewire.hook('message.processed', (message, component) => {
                initializeFlatpickr();
            });
        });

        function initializeFlatpickr() {
            // Standard date picker
            flatpickr('.flatpickr-date', {
                dateFormat: 'Y-m-d',
                locale: 'ar'
            });

            // Date and time picker
            flatpickr('.flatpickr-datetime', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i:S',
                locale: 'ar',
                time_24hr: true
            });

            // Month/Year picker - using monthSelectPlugin
            flatpickr('.flatpickr-month-year', {
                placeholder: 'التاريخ',
                altInput: true,
                allowInput: true,
                dateFormat: 'Y-m',
                altFormat: 'F Y',
                yearSelectorType: 'input',
                locale: {
                    months: {
                        shorthand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ],
                        longhand: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز',
                            'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'
                        ]
                    }
                },
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true,
                        dateFormat: 'Y-m',
                        altFormat: 'F Y',
                        theme: 'light'
                    })
                ]
            });
        }";
        }

        return $jsCode;
    }

    protected function generateSelect2ScriptForLivewire($lowerSingular)
    {
        return "
    // Better Select2 integration with Livewire - Fixed version
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2 for all modals
        function initSelect2ForModal(modalId) {
            const selectFields = document.querySelectorAll(modalId + ' select');

            selectFields.forEach(function(select) {
                if (select.id && !\$(select).hasClass('select2-hidden-accessible')) {
                    \$(select).select2({
                        placeholder: select.querySelector('option[value=\"\"]')?.textContent || 'اختر',
                        allowClear: true,
                        width: '100%',
                        dir: 'rtl',
                        dropdownParent: \$(modalId),
                        // Prevent Select2 from closing on select
                        closeOnSelect: true
                    });

                    // Enhanced Sync with Livewire v2 for wire:ignore elements
                    \$(select).on('select2:select select2:unselect', function (e) {
                        const fieldName = this.getAttribute('wire:model.defer') || this.getAttribute('wire:model');
                        if (fieldName) {
                            // For Livewire v2 with wire:ignore - use component.set()
                            const livewireEl = this.closest('[wire\\\\:id]');
                            if (livewireEl && window.livewire) {
                                const componentId = livewireEl.getAttribute('wire:id');
                                const component = window.livewire.find(componentId);
                                if (component) {
                                    component.set(fieldName, this.value);
                                }
                            } else {
                                // Fallback method - trigger change event
                                \$(this).trigger('change');
                            }
                        }
                    });
                }
            });
        }

        // Initialize for add modal
        \$('#add{$lowerSingular}Modal').on('shown.bs.modal', function () {
            setTimeout(() => {
                initSelect2ForModal('#add{$lowerSingular}Modal');
            }, 100);
        });

        // Initialize for edit modal
        \$('#edit{$lowerSingular}Modal').on('shown.bs.modal', function () {
            setTimeout(() => {
                initSelect2ForModal('#edit{$lowerSingular}Modal');
            }, 100);
        });

        // Reinitialize when Livewire updates - Livewire v2 syntax
        document.addEventListener('livewire:load', function() {
            window.livewire.hook('message.processed', (message, component) => {
                setTimeout(function() {
                    if (\$('#add{$lowerSingular}Modal').hasClass('show')) {
                        // Destroy and reinitialize
                        \$('#add{$lowerSingular}Modal select').each(function() {
                            if (\$(this).hasClass('select2-hidden-accessible')) {
                                \$(this).select2('destroy');
                            }
                        });
                        initSelect2ForModal('#add{$lowerSingular}Modal');
                    }

                    if (\$('#edit{$lowerSingular}Modal').hasClass('show')) {
                        // Destroy and reinitialize
                        \$('#edit{$lowerSingular}Modal select').each(function() {
                            if (\$(this).hasClass('select2-hidden-accessible')) {
                                \$(this).select2('destroy');
                            }
                        });
                        initSelect2ForModal('#edit{$lowerSingular}Modal');
                    }
                }, 150);
            });
        });

        // Clean up Select2 when modals are hidden
        \$('#add{$lowerSingular}Modal, #edit{$lowerSingular}Modal').on('hidden.bs.modal', function () {
            \$(this).find('select').each(function() {
                if (\$(this).hasClass('select2-hidden-accessible')) {
                    \$(this).select2('destroy');
                }
            });
        });
    });";
    }

    protected function createExportClass($name, $fields, $arName)
    {
        $singularName = Str::singular($name);

        // Create Exports directory if it doesn't exist
        $exportDir = base_path('app/Exports');
        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        // Generate field mappings for Excel
        $fieldMappings = [];
        foreach ($fields as $field) {
            $arName = $field['ar_name'] ?? $field['label'] ?? $field['name'];
            $fieldMappings[] = "'{$field['name']}' => '{$arName}'";
        }
        $fieldMappingsString = implode(",\n            ", $fieldMappings);

        // Generate data row mapping
        $dataRowMapping = [];
        foreach ($fields as $field) {
            $dataRowMapping[] = "\$item->{$field['name']}";
        }
        $dataRowMappingString = implode(",\n            ", $dataRowMapping);

        $exportContent = "<?php

namespace App\\Exports;

use App\\Models\\{$name}\\{$name};
use Maatwebsite\\Excel\\Concerns\\FromCollection;
use Maatwebsite\\Excel\\Concerns\\WithHeadings;
use Maatwebsite\\Excel\\Concerns\\WithMapping;
use Maatwebsite\\Excel\\Concerns\\WithStyles;
use PhpOffice\\PhpSpreadsheet\\Worksheet\\Worksheet;

class {$name}Export implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return {$name}::all();
    }

    public function headings(): array
    {
        return [
            {$fieldMappingsString}
        ];
    }

    public function map(\$item): array
    {
        return [
            {$dataRowMappingString}
        ];
    }

    public function styles(Worksheet \$sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}";

        $exportPath = base_path("app/Exports/{$name}Export.php");
        File::put($exportPath, $exportContent);
        $this->info("📊 Created Export class");
    }

    protected function createPdfTemplate($name, $fields, $arName)
    {
        $lowerName = strtolower($name);

        // Create exports views directory if it doesn't exist
        $viewsDir = base_path('resources/views/exports');
        if (!File::exists($viewsDir)) {
            File::makeDirectory($viewsDir, 0755, true);
        }

        // Generate table headers
        $tableHeaders = [];
        foreach ($fields as $field) {
            $arName = $field['ar_name'] ?? $field['label'] ?? $field['name'];
            $tableHeaders[] = "<th>{$arName}</th>";
        }
        $tableHeadersString = implode("\n                ", $tableHeaders);

        // Generate table data rows
        $tableDataRows = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'checkbox') {
                $trueLabel = $field['checkbox_true_label'] ?? 'نعم';
                $falseLabel = $field['checkbox_false_label'] ?? 'لا';
                $tableDataRows[] = "<td>{{ \$item->{$field['name']} ? '{$trueLabel}' : '{$falseLabel}' }}</td>";
            } else {
                $tableDataRows[] = "<td>{{ \$item->{$field['name']} }}</td>";
            }
        }
        $tableDataRowsString = implode("\n                    ", $tableDataRows);

        $pdfContent = "<!DOCTYPE html>
<html dir=\"rtl\">
<head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
    <title>تقرير {$arName}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Tahoma', 'Arial Unicode MS', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4A6CF7;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #4A6CF7;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }

        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #4A6CF7;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e3f2fd;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class=\"header\">
        <h1>تقرير {$arName}</h1>
    </div>

    <div class=\"date\">
        <strong>تاريخ التقرير:</strong> {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                {$tableHeadersString}
            </tr>
        </thead>
        <tbody>
            @foreach(\$data as \$item)
                <tr>
                    {$tableDataRowsString}
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class=\"footer\">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات</p>
        <p>© {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</body>
</html>";

        $pdfPath = base_path("resources/views/exports/{$lowerName}_pdf.blade.php");
        File::put($pdfPath, $pdfContent);
        $this->info("📄 Created PDF template");
    }

    /**
     * Add module navigation menu item
     */
    protected function addNavigationMenuItem($name, $arName)
    {
        try {
            // لا حاجة لإضافة شيء في contentNavbarLayout لأننا نستخدم النظام الديناميكي
            // سيتم إضافة الوحدة تلقائياً عبر DynamicMenuHelper::addMenuItemToProject
            $this->info("🧭 Navigation menu will be updated via dynamic menu system");
        } catch (\Exception $e) {
            $this->error("❌ Error adding navigation menu: " . $e->getMessage());
        }
    }

    /**
     * Add module to active open list in development tools section
     */
    protected function addToActiveOpenList(&$content, $name)
    {
        // لا حاجة لهذه الدالة مع النظام الديناميكي
        // ستتم إدارة active states تلقائياً
    }

    /**
     * Generate navigation menu item HTML
     */
    protected function generateNavigationMenuItem($name, $arName)
    {
        $icon = $this->getModuleIcon($name);

        return "                        {{-- {$arName} --}}
                        @can('{$name}')
                            <li class=\"menu-item {{ request()->is('{$name}') ? 'active' : '' }}\">
                                <a href=\"{{ Route('{$name}') }}\" class=\"menu-link\">
                                    <i class=\"{$icon}\"></i>
                                    <div>{$arName}</div>
                                </a>
                            </li>
                        @endcan
";
    }

    /**
     * Get appropriate icon for module based on name or custom icon
     */
    protected function getModuleIcon($name, $customIcon = null)
    {
        // إذا كانت هناك أيقونة مخصصة، استخدمها
        if (!empty($customIcon)) {
            return $customIcon;
        }

        $iconMap = [
            'Users' => 'mdi mdi-account-group',
            'Employees' => 'mdi mdi-badge-account',
            'Departments' => 'mdi mdi-office-building',
            'Categories' => 'mdi mdi-shape',
            'Products' => 'mdi mdi-package-variant',
            'Orders' => 'mdi mdi-cart',
            'Invoices' => 'mdi mdi-file-document',
            'Reports' => 'mdi mdi-chart-line',
            'Settings' => 'mdi mdi-cog',
            'Notifications' => 'mdi mdi-bell',
            'Messages' => 'mdi mdi-message',
            'Files' => 'mdi mdi-file-multiple',
            'Tasks' => 'mdi mdi-check-circle',
            'Projects' => 'mdi mdi-briefcase',
            'Customers' => 'mdi mdi-account-heart',
            'Suppliers' => 'mdi mdi-truck',
            'Inventory' => 'mdi mdi-warehouse',
            'Sales' => 'mdi mdi-currency-usd',
            'Purchases' => 'mdi mdi-shopping',
            'Payments' => 'mdi mdi-credit-card',
            'Events' => 'mdi mdi-calendar-check',
            'Bookings' => 'mdi mdi-calendar-clock',
            'Reviews' => 'mdi mdi-star',
            'Analytics' => 'mdi mdi-chart-bar',
            'Logs' => 'mdi mdi-text-box-search',
            'Backups' => 'mdi mdi-backup-restore',
            'Permissions' => 'mdi mdi-shield-account',
            'Roles' => 'mdi mdi-account-key',
            'Branches' => 'mdi mdi-source-branch',
            'Locations' => 'mdi mdi-map-marker',
            'Vehicles' => 'mdi mdi-car',
            'Drivers' => 'mdi mdi-steering',
            'Routes' => 'mdi mdi-map',
            'Schedules' => 'mdi mdi-clock-time-four',
            'Attendance' => 'mdi mdi-clock-check',
            'Payroll' => 'mdi mdi-currency-usd-circle',
            'Vacations' => 'mdi mdi-beach',
            'Dispatchs' => 'mdi mdi-send',
            'Documents' => 'mdi mdi-file-document-multiple',
            'Contracts' => 'mdi mdi-file-sign',
            'Courses' => 'mdi mdi-school',
            'Students' => 'mdi mdi-account-school',
            'Teachers' => 'mdi mdi-human-male-board',
            'Subjects' => 'mdi mdi-book-open-page-variant',
            'Exams' => 'mdi mdi-clipboard-text',
            'Grades' => 'mdi mdi-medal',
            'Libraries' => 'mdi mdi-library',
            'Books' => 'mdi mdi-book',
            'Magazines' => 'mdi mdi-newspaper',
            'Articles' => 'mdi mdi-post',
            'News' => 'mdi mdi-newspaper-variant',
            'Galleries' => 'mdi mdi-image-multiple',
            'Videos' => 'mdi mdi-video-multiple',
            'Audios' => 'mdi mdi-music-box-multiple',
        ];

        return $iconMap[$name] ?? 'mdi mdi-circle-outline';
    }

    /**
     * Remove module navigation menu item
     */
    public static function removeNavigationMenuItem($name)
    {
        // لا حاجة للحذف من contentNavbarLayout لأننا نستخدم النظام الديناميكي
        // سيتم حذف الوحدة تلقائياً عبر DynamicMenuHelper::removeMenuItem
        try {
            \App\Helpers\DynamicMenuHelper::removeMenuItem($name);
        } catch (\Exception $e) {
            // تجاهل الأخطاء في الحذف للأمان
        }
    }

    /**
     * Save module fields configuration for future editing
     */
    protected function saveModuleFieldsConfiguration($moduleName, $fields, $arName = null)
    {
        $this->info("💾 حفظ تكوين الحقول في قاعدة البيانات...");

        try {
            // حذف الحقول السابقة للوحدة لتجنب التكرار
            ModuleField::where('module_name', $moduleName)->delete();

            // إنشاء اسم الجدول
            $tableName = Str::snake(Str::plural($moduleName));

            // حفظ الحقول في قاعدة البيانات
            ModuleField::saveFieldsFromGenerator($moduleName, $fields, 'generator', $tableName, $arName);

            $this->info("✅ تم حفظ " . count($fields) . " حقل في قاعدة البيانات للوحدة: {$moduleName}");

            // إصلاح الحقول المحسوبة تلقائياً
            $this->fixCalculatedFieldsConfiguration($moduleName);

            // الاحتفاظ بحفظ JSON كنسخة احتياطية
            $configDir = storage_path('app/modules_config');

            if (!file_exists($configDir)) {
                mkdir($configDir, 0755, true);
            }

            $configFile = $configDir . '/' . strtolower($moduleName) . '_fields.json';

            $config = [
                'module_name' => $moduleName,
                'updated_at' => now()->toISOString(),
                'fields' => []
            ];

            foreach ($fields as $field) {
                $config['fields'][] = [
                    'name' => $field['name'],
                    'ar_name' => $field['ar_name'] ?? $field['name'],
                    'comment' => $field['comment'] ?? $field['name'],
                    'type' => $field['type'],
                    'required' => $field['required'] ?? false,
                    'unique' => $field['unique'] ?? false,
                    'searchable' => $field['searchable'] ?? true,
                    'show_in_table' => $field['show_in_table'] ?? true,
                    'show_in_search' => $field['show_in_search'] ?? true,
                    'show_in_forms' => $field['show_in_forms'] ?? true,
                    'size' => $field['size'] ?? '255',
                    'arabic_only' => $field['arabic_only'] ?? false,
                    'numeric_only' => $field['numeric_only'] ?? false,
                    'file_types' => $field['file_types'] ?? '',
                    'select_options' => $field['select_options'] ?? $field['options'] ?? [],
                    'select_source' => $field['select_source'] ?? 'manual',
                    'related_table' => $field['related_table'] ?? '',
                    'related_key' => $field['related_key'] ?? 'id',
                    'related_display' => $field['related_display'] ?? 'name',
                    'validation_rules' => $field['validation'] ?? $field['validation_rules'] ?? $field['rules'] ?? null,
                    'validation_messages' => $field['validation_messages'] ?? $field['messages'] ?? null,
                    'custom_attributes' => $field['custom_attributes'] ?? $field['attributes'] ?? $field['custom'] ?? null,
                    'checkbox_true_label' => $field['checkbox_true_label'] ?? 'نعم',
                    'checkbox_false_label' => $field['checkbox_false_label'] ?? 'لا',
                    // إضافة خصائص الحقول المحسوبة
                    'is_calculated' => $field['is_calculated'] ?? false,
                    'calculation_type' => $field['calculation_type'] ?? 'none',
                    'calculation_formula' => $field['calculation_formula'] ?? null,
                    'date_from_field' => $field['date_from_field'] ?? null,
                    'date_to_field' => $field['date_to_field'] ?? null,
                    'date_diff_unit' => $field['date_diff_unit'] ?? null,
                    'remaining_only' => $field['remaining_only'] ?? false,
                    'is_date_calculated' => $field['is_date_calculated'] ?? false,
                    // إضافة خصائص حساب الأوقات
                    'time_from_field' => $field['time_from_field'] ?? null,
                    'time_to_field' => $field['time_to_field'] ?? null,
                    'time_diff_unit' => $field['time_diff_unit'] ?? 'minutes',
                    'is_time_calculated' => $field['is_time_calculated'] ?? false,
                    'absolute_value' => $field['absolute_value'] ?? false
                ];
            }

            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->info("✅ تم حفظ نسخة احتياطية JSON أيضاً");
        } catch (\Exception $e) {
            $this->error("❌ خطأ في حفظ تكوين الحقول: " . $e->getMessage());

            // Fallback إلى JSON فقط
            $configDir = storage_path('app/modules_config');
            if (!file_exists($configDir)) {
                mkdir($configDir, 0755, true);
            }

            $configFile = $configDir . '/' . strtolower($moduleName) . '.json';

            $config = [];
            foreach ($fields as $field) {
                $config[$field['name']] = [
                    'type' => $field['type'],
                    'size' => $field['size'] ?? '',
                    'show_in_table' => $field['show_in_table'] ?? true,
                    'show_in_search' => $field['show_in_search'] ?? true,
                    'show_in_forms' => $field['show_in_forms'] ?? true,
                    'arabic_only' => $field['arabic_only'] ?? false,
                    'numeric_only' => $field['numeric_only'] ?? false,
                ];
            }

            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn("⚠️ تم الحفظ في JSON فقط بسبب خطأ في قاعدة البيانات");
        }
    }

    /**
     * فحص المايجريشن المعلقة
     */
    private function checkPendingMigrations()
    {
        try {
            // الحصول على جميع ملفات المايجريشن
            $migrationFiles = glob(database_path('migrations/*.php'));
            $allMigrations = [];

            foreach ($migrationFiles as $file) {
                $migration = pathinfo($file, PATHINFO_FILENAME);
                $allMigrations[] = $migration;
            }

            // الحصول على المايجريشن المنجزة من قاعدة البيانات
            $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();

            // العثور على المايجريشن المعلقة
            $pendingMigrations = array_diff($allMigrations, $ranMigrations);

            return array_values($pendingMigrations);
        } catch (\Exception $e) {
            // إذا حدث خطأ في فحص المايجريشن، أرجع مصفوفة فارغة
            $this->warn("تحذير: لا يمكن فحص حالة المايجريشن: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create TCPDF Export Controller in module folder
     */
    protected function createTcpdfController($name, $fields, $arName)
    {
        $singularName = Str::singular($name);

        // Generate field headers and data for TCPDF
        $tcpdfHeaders = $this->getTcpdfHeadersString($fields);
        $tcpdfData = $this->getTcpdfDataString($fields);

        $content = "<?php

namespace App\\Http\\Controllers\\{$name};

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use App\\Models\\{$name}\\{$name} as {$singularName}Model;
use Elibyy\\TCPDF\\Facades\\TCPDF;

class {$singularName}TcpdfExportController extends Controller
{
    public function __construct()
    {
        \$this->middleware('permission:" . strtolower($singularName) . "-export-pdf');
    }

    /**
     * Export PDF for {$name} using TCPDF
     */
    public function exportPdf()
    {
        try {
            \$data = {$singularName}Model::all();

            // إنشاء PDF جديد
            \$pdf = new \\TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // إعدادات PDF
            \$pdf->SetCreator('Laravel System');
            \$pdf->SetAuthor('إدارة النظام');
            \$pdf->SetTitle('تقرير {$arName}');
            \$pdf->SetSubject('تقرير شامل لـ {$arName}');

            // إعدادات اللغة العربية
            \$pdf->setLanguageArray(array(
                'a_meta_charset' => 'UTF-8',
                'a_meta_dir' => 'rtl',
                'a_meta_language' => 'ar',
                'w_page' => 'صفحة'
            ));

            // إعدادات الخط العربي
            \$pdf->SetFont('dejavusans', '', 12);

            // إعدادات الهوامش
            \$pdf->SetMargins(15, 20, 15);
            \$pdf->SetHeaderMargin(10);
            \$pdf->SetFooterMargin(10);

            // تعطيل الهيدر والفوتر الافتراضي
            \$pdf->setPrintHeader(false);
            \$pdf->setPrintFooter(false);

            // إضافة صفحة
            \$pdf->AddPage();

            // العنوان الرئيسي
            \$pdf->SetFont('dejavusans', 'B', 20);
            \$pdf->setRTL(true);
            \$pdf->Cell(0, 15, 'تقرير {$arName}', 0, 1, 'C');
            \$pdf->Ln(5);

            // تاريخ التقرير
            \$pdf->SetFont('dejavusans', '', 12);
            \$pdf->Cell(0, 10, 'تاريخ التقرير: ' . now()->format('Y-m-d H:i:s'), 0, 1, 'C');
            \$pdf->Ln(10);

            // رؤوس الجدول
            \$pdf->SetFont('dejavusans', 'B', 10);
            \$pdf->SetFillColor(74, 108, 247);
            \$pdf->SetTextColor(255, 255, 255);

            // Add table headers dynamically based on fields
            {$tcpdfHeaders}

            // بيانات الجدول
            \$pdf->SetFont('dejavusans', '', 9);
            \$pdf->SetTextColor(0, 0, 0);
            \$fill = false;

            foreach(\$data as \$item) {
                if(\$fill) {
                    \$pdf->SetFillColor(248, 249, 250);
                } else {
                    \$pdf->SetFillColor(255, 255, 255);
                }

                // Add table data dynamically based on fields
                {$tcpdfData}

                \$fill = !\$fill;
            }

            // فوتر التقرير
            \$pdf->Ln(10);
            \$pdf->SetFont('dejavusans', '', 10);
            \$pdf->Cell(0, 10, 'إجمالي عدد السجلات: ' . count(\$data), 0, 1, 'C');
            \$pdf->Cell(0, 10, 'تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات', 0, 1, 'C');
            \$pdf->Cell(0, 10, '© ' . date('Y') . ' - جميع الحقوق محفوظة', 0, 1, 'C');

            return \$pdf->Output('تقرير_{$arName}_' . now()->format('Y_m_d_H_i_s') . '.pdf', 'D');

        } catch (\\Exception \$e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء PDF: ' . \$e->getMessage()], 500);
        }
    }
}";

        $path = base_path("app/Http/Controllers/{$name}/{$singularName}TcpdfExportController.php");
        File::put($path, $content);
        $this->info("📄 Created TCPDF Export Controller in module folder");
    }

    /**
     * Create Print Controller in module folder
     */
    protected function createPrintController($name, $fields, $arName)
    {
        $singularName = Str::singular($name);
        $lowerName = strtolower($name);

        $content = "<?php

namespace App\\Http\\Controllers\\{$name};

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use App\\Models\\{$name}\\{$name} as {$singularName}Model;

class {$singularName}PrintController extends Controller
{
    public function __construct()
    {
        \$this->middleware('permission:" . strtolower($singularName) . "-export-pdf');
    }

    /**
     * Show print-friendly page for {$name}
     */
    public function printView()
    {
        try {
            \$data = {$singularName}Model::all();

            return view('exports.{$lowerName}_print', [
                'data' => \$data,
                'title' => 'تقرير {$arName}',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\\Exception \$e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . \$e->getMessage()], 500);
        }
    }
}";

        $path = base_path("app/Http/Controllers/{$name}/{$singularName}PrintController.php");
        File::put($path, $content);
        $this->info("🖨️ Created Print Controller in module folder");
    }

    /**
     * Create Print Template for direct browser printing
     */
    protected function createPrintTemplate($name, $fields, $arName)
    {
        $lowerName = strtolower($name);
        $singularName = Str::singular($name);

        // Create exports views directory if it doesn't exist
        $viewsDir = base_path('resources/views/exports');
        if (!File::exists($viewsDir)) {
            File::makeDirectory($viewsDir, 0755, true);
        }

        // Generate table headers
        $tableHeaders = ['<th>الرقم</th>'];
        if (empty($fields)) {
            $tableHeaders[] = '<th>الاسم</th>';
        } else {
            foreach ($fields as $field) {
                $fieldArName = $field['ar_name'] ?? $field['name'];
                $tableHeaders[] = "<th>{$fieldArName}</th>";
            }
        }
        $tableHeadersString = implode("\n                ", $tableHeaders);

        // Generate table data rows
        $tableDataRows = ['<td class="number">{{ $item->id }}</td>'];
        if (empty($fields)) {
            $tableDataRows[] = '<td class="arabic-text">{{ $item->name ?? \'غير محدد\' }}</td>';
        } else {
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                $fieldType = $field['type'] ?? 'text';

                if ($fieldType === 'checkbox') {
                    $trueLabel = $field['checkbox_true_label'] ?? 'مفعل';
                    $falseLabel = $field['checkbox_false_label'] ?? 'غير مفعل';
                    $tableDataRows[] = "<td class=\"arabic-text\">{{ \$item->{$fieldName} ? '{$trueLabel}' : '{$falseLabel}' }}</td>";
                } elseif ($fieldType === 'date') {
                    $tableDataRows[] = "<td class=\"number\">{{ \$item->{$fieldName} ? \\Carbon\\Carbon::parse(\$item->{$fieldName})->format('Y/m/d') : 'غير محدد' }}</td>";
                } elseif ($fieldType === 'number' || $fieldType === 'decimal') {
                    $tableDataRows[] = "<td class=\"number\">{{ number_format(\$item->{$fieldName} ?? 0, 2) }}</td>";
                } else {
                    $tableDataRows[] = "<td class=\"arabic-text\">{{ \$item->{$fieldName} ?? 'غير محدد' }}</td>";
                }
            }
        }
        $tableDataRowsString = implode("\n                    ", $tableDataRows);

        $printTemplate = "<!DOCTYPE html>
<html dir=\"rtl\" lang=\"ar\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>تقرير {$arName} - طباعة</title>
    <link href=\"https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&display=swap\" rel=\"stylesheet\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            direction: rtl;
            font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
        }

        body {
            font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 20px;
            background: white;
        }

        .no-print {
            display: block;
        }

        .print-only {
            display: none;
        }

        .controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #4A6CF7;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3b56e0;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4A6CF7;
        }

        .header h1 {
            font-size: 32px;
            color: #4A6CF7;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
            direction: rtl;
        }

        th, td {
            border: 2px solid #ddd;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #4A6CF7;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        td {
            background-color: #fff;
            color: #333;
        }

        tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }

        .arabic-text {
            direction: rtl;
            text-align: right;
            font-family: 'Noto Sans Arabic', Arial, sans-serif;
        }

        .number {
            direction: ltr;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        /* تحسينات للطباعة */
        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                margin: 0;
                padding: 15mm;
                font-size: 12px;
            }

            .header {
                margin-bottom: 30px;
                page-break-after: avoid;
            }

            table {
                font-size: 11px;
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            th {
                page-break-after: avoid;
            }

            .footer {
                page-break-before: avoid;
            }
        }

        @page {
            size: A4;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class=\"controls no-print\">
        <h3 style=\"margin-bottom: 15px;\">أدوات الطباعة</h3>
        <button onclick=\"window.print()\" class=\"btn btn-primary\">
            <i>🖨️</i> طباعة التقرير
        </button>
        <button onclick=\"window.close()\" class=\"btn btn-secondary\">
            <i>❌</i> إغلاق النافذة
        </button>
        <a href=\"{{ route('{$name}') }}\" class=\"btn btn-secondary\">
            <i>🔙</i> العودة للقائمة
        </a>
    </div>

    <div class=\"header\">
        <h1>تقرير {$arName}</h1>
    </div>

    <div class=\"date\">
        <strong>تاريخ التقرير:</strong> {{ \$generated_at ?? now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                {$tableHeadersString}
            </tr>
        </thead>
        <tbody>
            @foreach(\$data as \$item)
                <tr>
                    {$tableDataRowsString}
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class=\"footer\">
        <p><strong>إجمالي عدد السجلات:</strong> {{ count(\$data) }}</p>
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات</p>
        <p>&copy; {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>

    <script>
        // Auto-print functionality (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>";

        $templatePath = base_path("resources/views/exports/{$lowerName}_print.blade.php");
        File::put($templatePath, $printTemplate);
        $this->info("🖨️ Created Print Template: {$lowerName}_print.blade.php");
    }

    /**
     * إنشاء مجموعة أساسية للوحدة الرئيسية
     */
    protected function createBasicGroupForMainModule($name, $arName, $customIcon = null, $customOrder = null)
    {
        try {
            // التحقق من وجود المجموعة الأساسية
            $existingGroup = DB::table('basic_groups')
                ->where('name_en', $name)
                ->whereNull('deleted_at')
                ->first();

            if ($existingGroup) {
                return $existingGroup->id;
            }

            // تحديد الأيقونة والترتيب
            $icon = $customIcon ?: $this->getModuleIcon($name);
            $sortOrder = $customOrder ?: 999; // ترتيب منخفض للوحدات الجديدة إذا لم يحدد

            // إنشاء مجموعة أساسية جديدة
            $basicGroupId = DB::table('basic_groups')->insertGetId([
                'name_ar' => $arName,
                'name_en' => $name,
                'icon' => $icon,
                'description_ar' => "مجموعة " . $arName,
                'description_en' => $name . " Group",
                'sort_order' => $sortOrder,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("📁 Created basic group for main module: {$name} with icon: {$icon}");
            return $basicGroupId;

        } catch (\Exception $e) {
            $this->error("❌ Error creating basic group: " . $e->getMessage());
            return null;
        }
    }

    /**
     * إضافة basic_group_id للوحدة في dynamic-menu.php
     */
    protected function addBasicGroupIdToMenuItem($moduleName, $basicGroupId)
    {
        try {
            $configPath = config_path('dynamic-menu.php');
            $config = include $configPath;

            // البحث عن العنصر وإضافة basic_group_id
            foreach ($config['menu_items'] as &$item) {
                if ($item['type'] === 'item' && $item['permission'] === $moduleName) {
                    $item['basic_group_id'] = $basicGroupId;
                    break;
                }
            }

            // كتابة التكوين المحدث
            $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
            file_put_contents($configPath, $configContent);

            $this->info("✅ Added basic_group_id to dynamic menu item");

        } catch (\Exception $e) {
            $this->error("❌ Error updating dynamic menu: " . $e->getMessage());
        }
    }

    /**
     * دالة مساعدة لحذف الوحدة مع مراعاة kebab-case للمجلدات
     */
    public function deleteModuleFiles($moduleName)
    {
        try {
            // متغيرات kebab-case
            $kebabModuleName = Str::kebab($moduleName);

            // مسارات الملفات والمجلدات المراد حذفها
            $pathsToDelete = [
                // Controllers
                base_path("app/Http/Controllers/{$moduleName}"),

                // Livewire Components
                base_path("app/Http/Livewire/{$moduleName}"),

                // Models
                base_path("app/Models/{$moduleName}"),

                // Views (kebab-case للـ livewire)
                base_path("resources/views/livewire/{$kebabModuleName}"),
                base_path("resources/views/content/{$moduleName}"),

                // Exports
                base_path("app/Exports/{$moduleName}Export.php"),

                // PDF Views
                base_path("resources/views/exports/" . strtolower($moduleName) . "_pdf.blade.php"),
                base_path("resources/views/exports/" . strtolower($moduleName) . "_print.blade.php")
            ];

            foreach ($pathsToDelete as $path) {
                if (File::exists($path)) {
                    if (File::isDirectory($path)) {
                        File::deleteDirectory($path);
                        $this->info("🗂️ Deleted directory: {$path}");
                    } else {
                        File::delete($path);
                        $this->info("📄 Deleted file: {$path}");
                    }
                }
            }

            // حذف migration files
            $migrationPattern = database_path("migrations/*_create_" . strtolower($moduleName) . "_table.php");
            $migrationFiles = glob($migrationPattern);
            foreach ($migrationFiles as $migrationFile) {
                if (File::exists($migrationFile)) {
                    File::delete($migrationFile);
                    $this->info("📄 Deleted migration: {$migrationFile}");
                }
            }

            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error deleting module files: " . $e->getMessage());
            return false;
        }
    }

    /**
     * إصلاح الحقول المحسوبة للوقت والتاريخ تلقائياً
     */
    private function fixCalculatedFieldsConfiguration($moduleName)
    {
        try {
            $this->info("🔧 فحص وإصلاح الحقول المحسوبة للوحدة: {$moduleName}");

            // إصلاح حقول حساب الوقت
            $timeFields = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                         ->where('calculation_type', 'time_diff')
                                         ->where('is_time_calculated', false)
                                         ->get();

            foreach ($timeFields as $field) {
                $field->update(['is_time_calculated' => true]);
                $this->info("✅ تم إصلاح حقل الوقت: {$field->field_name}");
            }

            // إصلاح حقول حساب التاريخ
            $dateFields = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                         ->where('calculation_type', 'date_diff')
                                         ->where('is_date_calculated', false)
                                         ->get();

            foreach ($dateFields as $field) {
                $field->update(['is_date_calculated' => true]);
                $this->info("✅ تم إصلاح حقل التاريخ: {$field->field_name}");
            }

            if ($timeFields->count() > 0 || $dateFields->count() > 0) {
                $this->info("🎯 تم إصلاح " . ($timeFields->count() + $dateFields->count()) . " حقل محسوب");
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إصلاح الحقول المحسوبة: " . $e->getMessage());
        }
    }

    /**
     * Get integer validation messages for specific integer type
     */
    private function getIntegerValidationMessages($field)
    {
        $messages = [];
        $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
        $fieldName = $field['name'];

        if (isset($field['integer_type'])) {
            $integerType = $field['integer_type'];
            $isSigned = !isset($field['unsigned']) || !$field['unsigned'];

            switch ($integerType) {
                case 'tinyint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -128";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 127";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 3 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 255";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 3 أرقام";
                    }
                    break;

                case 'smallint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -32,768";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 32,767";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 5 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 65,535";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 5 أرقام";
                    }
                    break;

                case 'int':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -2,147,483,648";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 2,147,483,647";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 10 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 4,294,967,295";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 10 أرقام";
                    }
                    break;

                case 'bigint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -9,223,372,036,854,775,808";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 9,223,372,036,854,775,807";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 19 رقم";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 18,446,744,073,709,551,615";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 20 رقم";
                    }
                    break;
            }
        }

        return $messages;
    }

    /**
     * Get decimal validation messages for specific decimal precision and scale
     */
    private function getDecimalValidationMessages($field)
    {
        $messages = [];
        $arabicLabel = $field['ar_name'] ?? $field['arabic_label'] ?? $field['name'];
        $fieldName = $field['name'];

        if (isset($field['decimal_precision']) && isset($field['decimal_scale'])) {
            $precision = $field['decimal_precision']; // إجمالي عدد الأرقام
            $scale = $field['decimal_scale']; // عدد المراتب العشرية

            // حساب عدد الأرقام قبل العلامة العشرية
            $integerDigits = $precision - $scale;

            // إنشاء مثال ديناميكي
            $exampleInteger = str_repeat('1', min($integerDigits, 3)); // حد أقصى 3 أرقام للمثال
            $exampleDecimal = str_repeat('5', $scale);
            $example = $scale > 0 ? "{$exampleInteger}.{$exampleDecimal}" : $exampleInteger;

            // رسالة regex مخصصة
            if ($scale > 0) {
                // للأرقام العشرية
                $messages["{$fieldName}.regex"] = "{$arabicLabel} يجب أن يكون رقم عشري صحيح بحد أقصى {$integerDigits} أرقام قبل الفاصلة و{$scale} أرقام بعد الفاصلة (مثال: {$example})";
            } else {
                // للأرقام الصحيحة بدون مراتب عشرية
                $messages["{$fieldName}.regex"] = "{$arabicLabel} يجب أن يكون رقم صحيح بحد أقصى {$precision} أرقام (مثال: {$example})";
            }

            // رسالة numeric عامة
            $messages["{$fieldName}.numeric"] = "{$arabicLabel} يجب أن يكون رقم صالح";

            // رسالة للحد الأقصى للأرقام (اختيارية)
            $totalLength = $precision + ($scale > 0 ? 1 : 0); // +1 للفاصلة العشرية
            $messages["{$fieldName}.max"] = "{$arabicLabel} يجب ألا يتجاوز {$totalLength} خانات إجمالية";
        }

        return $messages;
    }
}
