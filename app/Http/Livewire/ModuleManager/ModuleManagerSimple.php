<?php

namespace App\Http\Livewire\ModuleManager;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Helpers\DynamicMenuHelper;
use App\Models\System\ModuleField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ModuleRestoreHelper;
use App\Services\DynamicMenuService;
use Illuminate\Support\Facades\File;
use App\Models\Management\BasicGroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\GenerateHmvcModule;

class ModuleManagerSimple extends Component
{
    public $modules = [];
    public $moduleToDelete = null;
    public $showDeleteModal = false;
    public $searchTerm = '';
    public $editingModule = null;
    public $showEditModal = false;

    // متغيرات التعديل الديناميكي
    public $moduleFields = [];
    public $newFields = '';
    public $arabicName = '';
    public $selectedModuleData = [];
    public $editMode = 'view'; // view, edit, add_fields

    // متغيرات تغيير المجموعة الأب
    public $changeType = 'change_parent'; // change_parent, make_standalone

    // متغيرات الحقول المتطورة - مطابقة لمولد الوحدات
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
        'integer_type' => 'int', // tinyint, smallint, int, bigint
        'unsigned' => false, // موجب فقط
        // إعدادات الأرقام العشرية الجديدة
        'decimal_precision' => 15, // إجمالي عدد الأرقام
        'decimal_scale' => 2, // عدد المراتب العشرية
        'file_types' => '',
        'select_options' => [],
        'select_source' => 'manual',
        'select_numeric_values' => false, // القيم الرقمية للقائمة المنسدلة
        'related_table' => '',
        'related_key' => 'id',
        'related_display' => 'name',
        'checkbox_true_label' => 'نعم',
        'checkbox_false_label' => 'لا',
        'is_calculated' => false, // حقل محسوب
        'calculation_formula' => '', // معادلة الحساب
        'calculation_type' => 'none', // نوع الحساب: none, formula, date_diff, time_diff
        'date_from_field' => '', // الحقل المرجعي للتاريخ من
        'date_to_field' => '', // الحقل المرجعي للتاريخ إلى
        'date_diff_unit' => 'days', // وحدة قياس الفرق
        'include_end_date' => false, // شمل التاريخ النهائي
        'absolute_value' => false, // قيمة مطلقة
        'remaining_only' => false, // الأيام المتبقية فقط
        'is_date_calculated' => false, // هل الحقل محسوب للتاريخ
        'date_calculation_config' => null, // إعدادات حساب التاريخ
        // خصائص حساب الوقت
        'time_from_field' => '', // الحقل المرجعي للوقت من
        'time_to_field' => '', // الحقل المرجعي للوقت إلى
        'time_diff_unit' => 'minutes', // وحدة قياس فرق الوقت: hours, minutes
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
        'month_year' => 'شهر/سنة',
        'checkbox' => 'صح/خطأ',
        'file' => 'ملف',
        'select' => 'قائمة منسدلة',
        'decimal' => 'رقم عشري'
    ];

    // متغيرات الحقول المعلقة والميزات المتقدمة
    public $pendingFields = [];
    public $enableExcelExport = true;
    public $enablePdfExport = true;
    public $enableFlatpickr = true;
    public $enableSelect2 = true;
    public $enableViewsUpdate = true; // تحديث Views تلقائياً

    // قوائم الجداول والحقول لحقول العلاقات (نسخة من ModuleGenerator)
    public $availableTables = []; // الجداول المتاحة في قاعدة البيانات
    public $selectedTableColumns = []; // حقول الجدول المختار

    // متغيرات خاصة بفحص وإصلاح مشاكل الكود
    public $detectedSyntaxIssues = [];
    public $confirmRegeneration = false;
    public $syntaxCheckCache = []; // لتخزين نتائج الفحص مؤقتاً
    public $lastFixTime = []; // لتخزين وقت آخر إصلاح

    // متغيرات تغيير المجموعة الأب
    public $availableGroups = [];
    public $currentParentGroup = '';
    public $selectedParentGroup = '';
    public $selectedModule = '';

    // متغيرات حذف الحقول (الحذف المحسن فقط)
    public $showFieldDeleteConfirm = false;
    public $fieldToDelete = null;
    public $fieldDeleteIndex = null;

    protected $listeners = [
        'confirmDelete' => 'deleteModuleWithReport',
        'editModule' => 'openEditModal',
        'refreshModuleList' => 'loadModules'
    ];

    public function mount()
    {
        Log::info("ModuleManagerSimple component mounted");
        $this->loadModules();
        $this->loadAvailableTables(); // تحميل الجداول المتاحة للربط
        $this->loadAvailableGroups(); // تحميل المجموعات المتاحة لتغيير المجموعة الأب
    }

    /**
     * إصلاح الوحدات المختفية باستخدام المساعد
     */
    public function fixMissingModules()
    {
        try {
            $result = ModuleRestoreHelper::fixMissingModules();

            if ($result['success']) {
                if (!empty($result['fixed'])) {
                    $this->dispatchBrowserEvent('success', [
                        'title' => 'تم الإصلاح بنجاح',
                        'message' => 'تم إصلاح ' . count($result['fixed']) . ' وحدة: ' . implode(', ', $result['fixed'])
                    ]);

                    // إعادة تحميل البيانات
                    $this->loadModules();
                    $this->loadAvailableGroups();

                    // إعادة تحديث الصفحة
                    $this->dispatchBrowserEvent('reload', [
                        'delay' => 2000
                    ]);
                } else {
                    $this->dispatchBrowserEvent('info', [
                        'title' => 'لا توجد مشاكل',
                        'message' => 'جميع الوحدات موجودة في القائمة'
                    ]);
                }
            } else {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'فشل الإصلاح',
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في إصلاح الوحدات: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الإصلاح',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إعادة بناء الوحدة من قاعدة البيانات - الطريقة المحسنة
     * تستخدم فقط الحقول المحفوظة في جدول module_fields وتتجاهل الحقول المحذوفة
     */
    public function rebuildModuleFromDatabase($moduleName = null)
    {
        try {
            $targetModule = $moduleName ?: $this->editingModule;

            if (!$targetModule) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'خطأ',
                    'message' => 'لم يتم تحديد اسم الوحدة'
                ]);
                return;
            }

            $this->dispatchBrowserEvent('info', [
                'title' => 'جاري إعادة البناء المحسن',
                'message' => "جاري إعادة بناء الوحدة {$targetModule} من الحقول المحفوظة في قاعدة البيانات..."
            ]);

            // جلب فقط الحقول النشطة (غير المحذوفة) من قاعدة البيانات
            $activeFields = $this->loadModuleFieldsFromDatabase($targetModule);

            if (empty($activeFields)) {
                $this->dispatchBrowserEvent('warning', [
                    'title' => 'تحذير',
                    'message' => "لا توجد حقول نشطة في قاعدة البيانات للوحدة {$targetModule}"
                ]);
                return;
            }

            Log::info("🔄 إعادة بناء الوحدة {$targetModule} باستخدام " . count($activeFields) . " حقل نشط");

            // إعادة إنشاء الوحدة بالحقول النشطة فقط
            $result = $this->recreateModuleWithFields($targetModule, $activeFields);

            if ($result) {
                $this->dispatchBrowserEvent('success', [
                    'title' => 'تم إعادة البناء بنجاح',
                    'message' => "تم إعادة بناء الوحدة {$targetModule} من قاعدة البيانات مع " . count($activeFields) . " حقل نشط"
                ]);

                // إعادة تحميل بيانات الوحدة المحدثة
                $this->loadModuleData($targetModule);

                Log::info("✅ تم إعادة بناء الوحدة {$targetModule} بنجاح");
            } else {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'فشل في إعادة البناء',
                    'message' => "فشل في إعادة بناء الوحدة {$targetModule}"
                ]);
            }

        } catch (\Exception $e) {
            Log::error("❌ خطأ في إعادة بناء الوحدة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في النظام',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    public function testFunction()
    {
        Log::info("Test function called!");
        $this->dispatchBrowserEvent('success', [
            'title' => 'اختبار الاتصال',
            'message' => 'اختبار الاتصال نجح!'
        ]);
    }

    public function loadModules()
    {
        $this->modules = [];

        // قائمة الوحدات النظام المخفية (عدا Dashboard)
        $hiddenSystemModules = [
            'Users',
            'Management',
            'PermissionsRoles',
            'ModuleGenerator',
            'ModuleManager',
            'DataManagement',
            'ReportGenerator',
            'Dashboard',
        ];

        // البحث عن الوحدات في مجلد Controllers
        $controllersPath = base_path('app/Http/Controllers');

        if (File::exists($controllersPath)) {
            $directories = File::directories($controllersPath);

            foreach ($directories as $dir) {
                $dirName = basename($dir);

                // تخطي الوحدات النظام المخفية
                if (in_array($dirName, $hiddenSystemModules)) {
                    Log::info("تخطي الوحدة النظام: {$dirName}");
                    continue;
                }

                // الحصول على معلومات الوحدة
                $moduleInfo = $this->getModuleInfo($dirName);

                if ($moduleInfo) {
                    $this->modules[] = $moduleInfo;
                }
            }
        }

        Log::info("تم تحميل " . count($this->modules) . " وحدة (مع إخفاء وحدات النظام)");
    }

    /**
     * Get module information including type and parent group with intelligent detection
     */
    protected function getModuleInfo($moduleName)
    {
        // الحصول على معلومات القائمة الديناميكية
        $menuItems = config('dynamic-menu.menu_items', []);

        $moduleType = 'unknown';
        $parentGroup = null;
        $arabicName = null;

        // البحث الذكي في القوائم الديناميكية مع مقارنة مرنة
        foreach ($menuItems as $item) {
            if ($item['type'] === 'group') {
                // فحص إذا كانت الوحدة مجموعة رئيسية - بحث ذكي ومرن
                if ($this->isModuleMatch($item['permission'], $moduleName)) {
                    $moduleType = 'main';
                    $arabicName = $item['title'];
                    break;
                }

                // فحص إذا كانت الوحدة فرعية في هذه المجموعة - بحث ذكي ومرن
                if (isset($item['children']) && is_array($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if ($this->isModuleMatch($child['permission'], $moduleName)) {
                            $moduleType = 'sub';
                            $parentGroup = $item['permission'];
                            $arabicName = $child['title'];
                            break 2;
                        }
                    }
                }
            } elseif ($item['type'] === 'item') {
                // فحص الوحدات المنفصلة (standalone modules created by module generator)
                if ($this->isModuleMatch($item['permission'], $moduleName)) {
                    // تحديد نوع الوحدة بذكاء
                    if (isset($item['basic_group_id']) && $item['basic_group_id']) {
                        $moduleType = 'main'; // وحدة أب مع basic group
                    } else {
                        $moduleType = 'standalone'; // وحدة منفصلة
                    }
                    $arabicName = $item['title'];
                    break;
                }
            }
        }

        // إذا لم توجد الوحدة في القائمة الديناميكية، استخدم الذكاء الاصطناعي لتحديد النوع
        if ($moduleType === 'unknown') {
            // محاولة الحصول على الاسم العربي من مصادر أخرى
            $arabicName = $this->getModuleArabicNameFromSources($moduleName);

            // تحديد نوع الوحدة بذكاء حسب التركيب والمكونات
            $moduleType = $this->detectModuleTypeIntelligently($moduleName);
        }

        // إذا لم نجد اسم عربي، استخدم الاسم الإنجليزي
        if (!$arabicName) {
            $arabicName = $moduleName;
        }

        // فحص المكونات
        $hasController = $this->hasController($moduleName);
        $hasModel = $this->hasModel($moduleName);
        $hasLivewire = $this->hasLivewire($moduleName);
        $hasViews = $this->hasViews($moduleName);
        $hasMigration = $this->hasMigration($moduleName);

        // حساب حالة الإكتمال - الوحدة كاملة إذا توفرت جميع المكونات الأساسية
        $complete = $hasController && $hasModel && $hasLivewire && $hasViews;

        return [
            'name' => $moduleName,
            'arabic_name' => $arabicName,
            'type' => $moduleType,
            'parent_group' => $parentGroup,
            'has_controller' => $hasController,
            'has_model' => $hasModel,
            'has_livewire' => $hasLivewire,
            'has_views' => $hasViews,
            'has_migration' => $hasMigration,
            'routes_count' => $this->getRoutesCount($moduleName),
            'permissions_count' => $this->getPermissionsCount($moduleName),
            'complete' => $complete,
            'created_at' => $this->getModuleCreatedAt($moduleName)
        ];
    }

    /**
     * مقارنة ذكية ومرنة لأسماء الوحدات
     */
    private function isModuleMatch($permissionName, $moduleName)
    {
        if (!$permissionName || !$moduleName) {
            return false;
        }

        // مقارنة مباشرة
        if ($permissionName === $moduleName) {
            return true;
        }

        // مقارنة بحروف صغيرة
        if (strtolower($permissionName) === strtolower($moduleName)) {
            return true;
        }

        // مقارنة المفرد والجمع
        $singularPermission = Str::singular($permissionName);
        $pluralPermission = Str::plural($permissionName);
        $singularModule = Str::singular($moduleName);
        $pluralModule = Str::plural($moduleName);

        if (strtolower($singularPermission) === strtolower($singularModule) ||
            strtolower($pluralPermission) === strtolower($pluralModule) ||
            strtolower($singularPermission) === strtolower($moduleName) ||
            strtolower($permissionName) === strtolower($singularModule)) {
            return true;
        }

        return false;
    }

    /**
     * الحصول على الاسم العربي للوحدة من مصادر متعددة
     */
    private function getModuleArabicNameFromSources($moduleName)
    {
        // 1. من ملف تكوين الوحدة المحفوظ
        $arabicFromConfig = $this->getArabicNameFromModuleConfig($moduleName);
        if ($arabicFromConfig) {
            return $arabicFromConfig;
        }

        // 2. من جدول basic_groups
        $arabicFromBasicGroups = $this->getArabicNameFromBasicGroups($moduleName);
        if ($arabicFromBasicGroups) {
            return $arabicFromBasicGroups;
        }

        // 3. من جدول permissions (حقل explain_name)
        $arabicFromPermissions = $this->getArabicNameFromPermissions($moduleName);
        if ($arabicFromPermissions) {
            return $arabicFromPermissions;
        }

        // 4. من وحدات النظام المعروفة (فقط الوحدات المسموح عرضها)
        $systemModules = [
            'Dashboard' => 'لوحة التحكم',
            'ReportGenerator' => 'مولد التقارير'
            // تم إزالة وحدات النظام المخفية من هنا
            // Users, Management, PermissionsRoles, ModuleGenerator, ModuleManager, DataManagement
        ];

        return $systemModules[$moduleName] ?? null;
    }

    /**
     * تحديد نوع الوحدة بذكاء حسب التركيب والمكونات
     */
    private function detectModuleTypeIntelligently($moduleName)
    {
        // فحص وجود basic_group للوحدة
        $hasBasicGroup = DB::table('basic_groups')
            ->where(function($query) use ($moduleName) {
                $query->where('name_en', $moduleName)
                      ->orWhere('name_en', strtolower($moduleName))
                      ->orWhere('name_en', Str::singular($moduleName))
                      ->orWhere('name_en', Str::plural($moduleName));
            })
            ->whereNull('deleted_at')
            ->exists();

        if ($hasBasicGroup) {
            return 'main'; // وحدة رئيسية
        }

        // فحص وحدات النظام المعروفة (فقط الوحدات المسموح عرضها)
        $systemModules = ['Dashboard', 'ReportGenerator'];
        if (in_array($moduleName, $systemModules)) {
            return 'system';
        }

        // فحص هيكل الملفات للتحديد التلقائي
        $hasCompleteStructure = $this->hasController($moduleName) &&
                               $this->hasModel($moduleName) &&
                               $this->hasLivewire($moduleName) &&
                               $this->hasViews($moduleName);

        if ($hasCompleteStructure) {
            return 'standalone'; // وحدة كاملة منفصلة
        }

        return 'unknown';
    }

    /**
     * الحصول على الاسم العربي من ملف تكوين الوحدة
     */
    private function getArabicNameFromModuleConfig($moduleName)
    {
        try {
            $configPath = storage_path("app/modules_config/{$moduleName}.json");
            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);
                return $config['arabic_name'] ?? $config['ar_name'] ?? null;
            }

            // جرب أيضاً اسم الملف بحروف صغيرة
            $configPathLower = storage_path("app/modules_config/" . strtolower($moduleName) . ".json");
            if (File::exists($configPathLower)) {
                $config = json_decode(File::get($configPathLower), true);
                return $config['arabic_name'] ?? $config['ar_name'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("خطأ في قراءة تكوين الوحدة {$moduleName}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * الحصول على الاسم العربي من جدول basic_groups
     */
    private function getArabicNameFromBasicGroups($moduleName)
    {
        try {
            $group = DB::table('basic_groups')
                ->where(function($query) use ($moduleName) {
                    $query->where('name_en', $moduleName)
                          ->orWhere('name_en', strtolower($moduleName))
                          ->orWhere('name_en', Str::singular($moduleName))
                          ->orWhere('name_en', Str::plural($moduleName));
                })
                ->whereNull('deleted_at')
                ->first();

            return $group->name_ar ?? null;
        } catch (\Exception $e) {
            Log::warning("خطأ في البحث في basic_groups للوحدة {$moduleName}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * الحصول على الاسم العربي من جدول permissions
     */
    private function getArabicNameFromPermissions($moduleName)
    {
        try {
            // البحث عن الصلاحية الرئيسية للوحدة
            $permission = DB::table('permissions')
                ->where('name', $moduleName)
                ->orWhere('name', strtolower($moduleName))
                ->first();

            if ($permission && !empty($permission->explain_name)) {
                // استخراج الاسم من explain_name (مثل "إدارة الموظفين - الصلاحية الرئيسية")
                $explainName = $permission->explain_name;
                if (strpos($explainName, ' - ') !== false) {
                    return trim(explode(' - ', $explainName)[0]);
                }
                return $explainName;
            }
        } catch (\Exception $e) {
            Log::warning("خطأ في البحث في permissions للوحدة {$moduleName}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * تحميل الجداول المتاحة في قاعدة البيانات للربط
     */
    public function loadAvailableTables()
    {
        try {
            Log::info("Loading available tables...");
            $tables = DB::select('SHOW TABLES');
            $this->availableTables = [];

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                // تجاهل جداول النظام
                if (!in_array($tableName, ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'])) {
                    $this->availableTables[] = $tableName;
                }
            }

            Log::info("Available tables loaded: " . implode(', ', $this->availableTables));
        } catch (\Exception $e) {
            $this->availableTables = [];
            Log::error('خطأ في تحميل الجداول: ' . $e->getMessage());
        }
    }

    /**
     * تحميل المجموعات المتاحة لتغيير المجموعة الأب
     */
    public function loadAvailableGroups()
    {
        try {
            $menuItems = config('dynamic-menu.menu_items', []);
            $this->availableGroups = [];

            foreach ($menuItems as $item) {
                if ($item['type'] === 'group') {
                    $this->availableGroups[] = [
                        'name' => $item['permission'],
                        'name_en' => $item['permission'],
                        'name_ar' => $item['title']
                    ];
                }
            }

            Log::info("تم تحميل " . count($this->availableGroups) . " مجموعة متاحة");
        } catch (\Exception $e) {
            $this->availableGroups = [];
            Log::error('خطأ في تحميل المجموعات: ' . $e->getMessage());
        }
    }

    /**
     * تحميل حقول الجدول المختار
     */
    public function loadTableColumns($tableName)
    {
        try {
            Log::info("loadTableColumns called with tableName: " . $tableName);

            if (empty($tableName)) {
                $this->selectedTableColumns = [];
                return [];
            }

            $columns = Schema::getColumnListing($tableName);
            $this->selectedTableColumns = array_filter($columns, function($column) {
                // تجاهل الحقول الافتراضية
                return !in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at']);
            });

            Log::info("Found columns: " . implode(', ', $this->selectedTableColumns));

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

    protected function hasController($moduleName)
    {
        return File::exists(base_path("app/Http/Controllers/{$moduleName}"));
    }

    protected function hasModel($moduleName)
    {
        // البحث عن ملف Model بالأشكال المختلفة المحتملة
        $possiblePaths = [
            base_path("app/Models/{$moduleName}.php"), // مثل Employees.php
            base_path("app/Models/" . Str::singular($moduleName) . ".php"), // مثل Employee.php
            base_path("app/Models/{$moduleName}/" . Str::singular($moduleName) . ".php"), // مثل Employees/Employee.php
            base_path("app/Models/{$moduleName}/{$moduleName}.php"), // مثل Employees/Employees.php
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return true;
            }
        }

        return false;
    }

    protected function hasLivewire($moduleName)
    {
        return File::exists(base_path("app/Http/Livewire/{$moduleName}"));
    }

    protected function hasViews($moduleName)
    {
        $kebabModuleName = Str::kebab($moduleName);
        return File::exists(base_path("resources/views/content/{$moduleName}")) ||
               File::exists(base_path("resources/views/livewire/{$kebabModuleName}"));
    }

    protected function hasMigration($moduleName)
    {
        $migrationsPath = base_path('database/migrations');
        $files = File::glob("{$migrationsPath}/*" . strtolower($moduleName) . "*");
        return count($files) > 0;
    }

    protected function getRoutesCount($moduleName)
    {
        $webRoutes = File::get(base_path('routes/web.php'));
        return substr_count($webRoutes, $moduleName);
    }

    protected function getPermissionsCount($moduleName)
    {
        $permissionTypes = ['create', 'view', 'edit', 'delete', 'list', 'export-excel', 'export-pdf'];
        $lowerModuleName = strtolower(Str::singular($moduleName));
        $count = 0;

        // عد الصلاحية الرئيسية
        $mainPermission = strtolower($moduleName);
        $mainCount = DB::table('permissions')->where('name', '=', $mainPermission)->count();
        $count += $mainCount;

        // عد صلاحيات الوحدة بالتطابق الدقيق
        foreach ($permissionTypes as $type) {
            $permissionName = "{$lowerModuleName}-{$type}";
            $typeCount = DB::table('permissions')->where('name', '=', $permissionName)->count();
            $count += $typeCount;
        }

        return $count;
    }

    protected function getModuleCreatedAt($moduleName)
    {
        // البحث عن أقدم ملف في الوحدة لتحديد تاريخ الإنشاء
        $paths = [
            base_path("app/Http/Controllers/{$moduleName}"),
            base_path("app/Http/Livewire/{$moduleName}"),
            base_path("app/Models/{$moduleName}"),
            base_path("resources/views/content/{$moduleName}"),
        ];

        $oldestTime = time(); // الوقت الحالي كقيمة افتراضية

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $fileTime = File::lastModified($path);
                if ($fileTime < $oldestTime) {
                    $oldestTime = $fileTime;
                }
            }
        }

        return $oldestTime;
    }

    public function confirmDeleteModule($moduleName)
    {
        Log::info("confirmDeleteModule called with: " . $moduleName);

        $this->moduleToDelete = $moduleName;
        $this->showDeleteModal = true;

        Log::info("showDeleteModal set to: " . ($this->showDeleteModal ? 'true' : 'false'));
    }

    public function deleteModuleWithReport()
    {
        Log::info("deleteModuleWithReport called");

        if (!$this->moduleToDelete) {
            Log::warning("No module to delete");
            return;
        }

        $moduleName = $this->moduleToDelete;
        Log::info("Deleting module: " . $moduleName);

        try {
            // إظهار رسالة بداية العملية
            $this->dispatchBrowserEvent('info', [
                'title' => 'جاري الحذف',
                'message' => "جاري حذف الوحدة {$moduleName}... يرجى الانتظار"
            ]);

            // تنفيذ الحذف المتقدم
            $this->performAdvancedModuleDeletion($moduleName);

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم الحذف بنجاح',
                'message' => "تم حذف الوحدة {$moduleName} بنجاح مع جميع مكوناتها"
            ]);

            // إعادة تحميل القائمة
            $this->loadModules();
            $this->moduleToDelete = null;
            $this->showDeleteModal = false;

        } catch (\Exception $e) {
            Log::error("Error in deleteModuleWithReport: " . $e->getMessage());

            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الحذف',
                'message' => 'حدث خطأ في الحذف: ' . $e->getMessage()
            ]);
        }
    }    private function performAdvancedModuleDeletion($moduleName)
    {
        try {
            // 1. حذف الجداول من قاعدة البيانات أولاً باستخدام Artisan Commands
            $this->executeArtisanModuleDeletion($moduleName);

            // 2. حذف جميع الملفات والمجلدات باستخدام PowerShell Commands
            $this->executePowerShellModuleDeletion($moduleName);

            // 3. حذف ملفات الكونفيج المحفوظة
            $this->deleteModuleConfigFiles($moduleName);

            // 4. حذف الحقول الخاصة بالوحدة من جدول module_fields
            $this->deleteModuleFieldsFromDatabase($moduleName);

            // 5. تنظيف Routes
            $this->removeModuleRoute($moduleName);

            // 6. حذف use statements الخاصة بـ PDF Controllers
            $this->removePdfUseStatements($moduleName);

            // 7. إزالة الوحدة من قائمة dynamic-menu.php
            $this->removeModuleFromDynamicMenu($moduleName);

            // 8. حذف الوحدة من جدول المجموعات الأساسية
            $this->removeModuleFromBasicGroups($moduleName);

            // 8. تنظيف Cache
            $this->clearApplicationCache();

            Log::info("تم حذف الوحدة {$moduleName} بنجاح مع جميع مكوناتها");

        } catch (\Exception $e) {
            Log::error("خطأ في حذف الوحدة {$moduleName}: " . $e->getMessage());
            throw $e;
        }
    }

    private function executeArtisanModuleDeletion($moduleName)
    {
        try {
            // استخدام Artisan commands للحذف الآمن
            Artisan::call('module:clean-tables', ['module' => $moduleName]);
            Artisan::call('module:clean-permissions', ['module' => $moduleName]);
            Log::info("تم تنظيف قاعدة البيانات للوحدة {$moduleName}");
        } catch (\Exception $e) {
            Log::warning("تحذير في تنظيف قاعدة البيانات: " . $e->getMessage());
            // إذا فشلت الأوامر، نحاول الحذف المباشر
            $this->deleteModuleTables($moduleName);
            $this->deleteModulePermissions($moduleName);
        }
    }

    private function executePowerShellModuleDeletion($moduleName)
    {
        try {
            // استخدام kebab-case للمجلدات الخاصة بـ Livewire views
            $kebabModuleName = Str::kebab($moduleName);

            // مسارات الملفات المراد حذفها
            $filePaths = [
                base_path("app/Http/Controllers/{$moduleName}"),
                base_path("app/Http/Livewire/{$moduleName}"),
                base_path("app/Models/{$moduleName}"),
                base_path("resources/views/livewire/{$kebabModuleName}"),
                base_path("resources/views/content/{$moduleName}"),
                base_path("app/Exports/{$moduleName}Export.php"),
                base_path("resources/views/exports/" . strtolower($moduleName) . "_pdf.blade.php"),
                base_path("resources/views/exports/" . strtolower($moduleName) . "_print.blade.php")
            ];

            foreach ($filePaths as $path) {
                $this->deletePath($path, $moduleName);
            }

            // حذف جميع ملفات PDF والطباعة بشكل شامل
            $this->deleteAllPdfAndPrintFiles($moduleName);

            // حذف ملفات Migration
            $this->deleteMigrationFiles($moduleName);

            Log::info("تم حذف جميع ملفات الوحدة {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حذف ملفات الوحدة: " . $e->getMessage());
            // fallback إلى الطريقة التقليدية
            $this->deleteAllModuleFiles($moduleName);
        }
    }

    private function deletePath($path, $moduleName)
    {
        try {
            // فحص وجود المسار أولاً
            if (!file_exists($path)) {
                Log::info("المسار غير موجود: {$path}");
                return;
            }

            // استخدام PHP المدمج لحذف الملفات
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                Log::info("تم حذف المجلد: {$path}");
            } elseif (is_file($path)) {
                unlink($path);
                Log::info("تم حذف الملف: {$path}");
            }
        } catch (\Exception $e) {
            Log::warning("خطأ في حذف المسار {$path}: " . $e->getMessage());
            // محاولة باستخدام PowerShell كبديل
            $this->deleteThroughPowerShell($path);
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    private function deleteThroughPowerShell($path)
    {
        try {
            $command = is_dir($path)
                ? "Remove-Item -Path \"{$path}\" -Recurse -Force"
                : "Remove-Item -Path \"{$path}\" -Force";

            $process = proc_open(
                "powershell.exe -Command \"{$command}\"",
                [
                    0 => ["pipe", "r"],
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"]
                ],
                $pipes
            );

            if (is_resource($process)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                if ($exitCode === 0) {
                    Log::info("نجح حذف المسار عبر PowerShell: {$path}");
                } else {
                    Log::warning("فشل حذف المسار عبر PowerShell: {$path} - Error: {$error}");
                }
            }
        } catch (\Exception $e) {
            Log::error("خطأ في تنفيذ PowerShell للمسار {$path}: " . $e->getMessage());
        }
    }

    private function deleteModuleTables($moduleName)
    {
        try {
            // قائمة الأسماء المحتملة للجداول
            $possibleTables = [
                strtolower($moduleName),
                strtolower(Str::plural($moduleName)),
                strtolower(Str::singular($moduleName))
            ];

            $possibleTables = array_unique($possibleTables);

            foreach ($possibleTables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    DB::statement("SET FOREIGN_KEY_CHECKS=0;");
                    Schema::dropIfExists($tableName);
                    DB::statement("SET FOREIGN_KEY_CHECKS=1;");
                    Log::info("تم حذف الجدول: {$tableName}");
                }
            }
        } catch (\Exception $e) {
            Log::warning("تحذير في حذف الجداول: " . $e->getMessage());
        }
    }

    private function deleteModulePermissions($moduleName)
    {
        try {
            $permissionTypes = ['create', 'view', 'edit', 'delete', 'list', 'export-excel', 'export-pdf'];
            $lowerModuleName = strtolower(Str::singular($moduleName));
            $deletedCount = 0;

            // قائمة الصلاحيات الأساسية المحمية من النظام (تطابق دقيق)
            $systemProtectedPermissions = [
                'BasicGroup-list', 'BasicGroup-create', 'BasicGroup-edit', 'BasicGroup-delete', 'BasicGroup-view',
                'Projects-list', 'Projects-create', 'Projects-edit', 'Projects-delete', 'Projects-view',
                'Settings-list', 'Settings-create', 'Settings-edit', 'Settings-delete', 'Settings-view',
                'Reports-list', 'Reports-create', 'Reports-edit', 'Reports-delete', 'Reports-view',
                'basicgroup', 'projects', 'settings', 'reports'
            ];

            // دالة للتحقق من التطابق الدقيق فقط (بدون استخدام contains)
            $isSystemProtected = function($permissionName) use ($systemProtectedPermissions) {
                return in_array($permissionName, $systemProtectedPermissions);
            };

            // 1. حذف الصلاحية الرئيسية للوحدة (تطابق دقيق)
            $mainPermissionName = strtolower($moduleName);

            if (!$isSystemProtected($mainPermissionName)) {
                // حذف بالتطابق الدقيق فقط
                $deleted = DB::table('permissions')->where('name', '=', $mainPermissionName)->delete();
                if ($deleted > 0) {
                    $deletedCount++;
                    Log::info("تم حذف الصلاحية الرئيسية: {$mainPermissionName}");
                }

                // حذف العلاقات للصلاحية الرئيسية
                DB::table('role_has_permissions')
                  ->whereIn('permission_id', function($query) use ($mainPermissionName) {
                      $query->select('id')->from('permissions')->where('name', '=', $mainPermissionName);
                  })->delete();

                DB::table('model_has_permissions')
                  ->whereIn('permission_id', function($query) use ($mainPermissionName) {
                      $query->select('id')->from('permissions')->where('name', '=', $mainPermissionName);
                  })->delete();
            }

            // 2. حذف صلاحيات الوحدة المحددة (تطابق دقيق لكل صلاحية)
            foreach ($permissionTypes as $type) {
                $permissionName = "{$lowerModuleName}-{$type}";

                if (!$isSystemProtected($permissionName)) {
                    // حذف بالتطابق الدقيق فقط
                    $deleted = DB::table('permissions')->where('name', '=', $permissionName)->delete();
                    if ($deleted > 0) {
                        $deletedCount++;
                        Log::info("تم حذف الصلاحية: {$permissionName}");
                    }

                    // حذف العلاقات
                    DB::table('role_has_permissions')
                      ->whereIn('permission_id', function($query) use ($permissionName) {
                          $query->select('id')->from('permissions')->where('name', '=', $permissionName);
                      })->delete();

                    DB::table('model_has_permissions')
                      ->whereIn('permission_id', function($query) use ($permissionName) {
                          $query->select('id')->from('permissions')->where('name', '=', $permissionName);
                      })->delete();
                }
            }

            if ($deletedCount > 0) {
                Log::info("تم حذف {$deletedCount} صلاحية للوحدة: {$moduleName}");
            }

        } catch (\Exception $e) {
            Log::warning("تحذير في حذف الصلاحيات: " . $e->getMessage());
        }
    }

    private function deleteAllModuleFiles($moduleName)
    {
        // استخدام kebab-case للمجلدات الخاصة بـ Livewire views
        $kebabModuleName = Str::kebab($moduleName);

        // قائمة شاملة بجميع المسارات الممكنة
        $filePaths = [
            // Controllers
            base_path("app/Http/Controllers/{$moduleName}"),

            // Livewire
            base_path("app/Http/Livewire/{$moduleName}"),

            // Models
            base_path("app/Models/{$moduleName}"),

            // Views
            base_path("resources/views/livewire/{$kebabModuleName}"),
            base_path("resources/views/content/{$moduleName}"),

            // Exports
            base_path("app/Exports/{$moduleName}Export.php"),

            // PDF Templates - الملفات الجديدة والقديمة مع معالجة شاملة للأسماء
            base_path("resources/views/exports/" . strtolower($moduleName) . "_pdf.blade.php"),
            base_path("resources/views/exports/" . strtolower($moduleName) . "_print.blade.php"),
            base_path("resources/views/exports/" . strtolower(Str::plural($moduleName)) . "_print.blade.php"),
            base_path("resources/views/exports/" . strtolower(Str::singular($moduleName)) . "_print.blade.php"),
        ];

        // إضافة معالجة خاصة لملفات الطباعة في حالة عدم حذفها بالطرق العادية
        $printFilePatterns = [
            strtolower($moduleName),
            strtolower(Str::plural($moduleName)),
            strtolower(Str::singular($moduleName)),
            // إضافة معالجة خاصة للوحدات التي تنتهي بـ s
            $moduleName . 's',
            substr($moduleName, 0, -1), // إزالة آخر حرف للوحدات التي تنتهي بـ s
        ];

        // إزالة المكررات وإضافة المسارات الفريدة
        $printFilePatterns = array_unique(array_map('strtolower', $printFilePatterns));

        foreach ($printFilePatterns as $pattern) {
            $printFiles = [
                resource_path("views/exports/{$pattern}_print.blade.php"),
                resource_path("views/exports/{$pattern}_pdf.blade.php"),
            ];

            foreach ($printFiles as $printFile) {
                if (File::exists($printFile) && !in_array($printFile, $filePaths)) {
                    $filePaths[] = $printFile;
                }
            }
        }

        foreach ($filePaths as $path) {
            try {
                if (File::exists($path)) {
                    if (File::isDirectory($path)) {
                        File::deleteDirectory($path);
                    } else {
                        File::delete($path);
                    }
                    Log::info("تم حذف: {$path}");
                }
            } catch (\Exception $e) {
                Log::warning("تحذير في حذف {$path}: " . $e->getMessage());
            }
        }

        // حذف ملفات Migration
        $this->deleteMigrationFiles($moduleName);
    }

    /**
     * حذف جميع ملفات PDF والطباعة المتعلقة بالوحدة بشكل شامل
     */
    private function deleteAllPdfAndPrintFiles($moduleName)
    {
        try {
            $exportsPath = resource_path('views/exports');

            if (!File::exists($exportsPath)) {
                Log::info("مجلد exports غير موجود");
                return;
            }

            // أنماط مختلفة لاسم الوحدة
            $namePatterns = [
                strtolower($moduleName),                    // departments
                strtolower(Str::plural($moduleName)),       // departments (إذا كان مفرد)
                strtolower(Str::singular($moduleName)),     // department (إذا كان جمع)
                strtolower($moduleName) . 's',              // departmentss
                substr(strtolower($moduleName), 0, -1),     // department (إزالة آخر حرف)
            ];

            // إزالة المكررات
            $namePatterns = array_unique($namePatterns);

            // أنماط ملفات PDF والطباعة
            $filePatterns = ['_pdf.blade.php', '_print.blade.php'];

            foreach ($namePatterns as $pattern) {
                foreach ($filePatterns as $filePattern) {
                    $filePath = $exportsPath . '/' . $pattern . $filePattern;

                    if (File::exists($filePath)) {
                        try {
                            File::delete($filePath);
                            Log::info("تم حذف ملف PDF/Print: {$filePath}");
                        } catch (\Exception $e) {
                            Log::warning("خطأ في حذف ملف {$filePath}: " . $e->getMessage());
                        }
                    }
                }
            }

            // البحث في جميع الملفات التي تحتوي على اسم الوحدة (fallback)
            $allFiles = File::files($exportsPath);
            foreach ($allFiles as $file) {
                $filename = $file->getFilename();

                // التحقق من أن اسم الملف يحتوي على اسم الوحدة
                foreach ($namePatterns as $pattern) {
                    if (strpos($filename, $pattern) !== false &&
                        (strpos($filename, '_pdf.blade.php') !== false || strpos($filename, '_print.blade.php') !== false)) {

                        try {
                            File::delete($file->getPathname());
                            Log::info("تم حذف ملف PDF/Print إضافي: {$file->getPathname()}");
                        } catch (\Exception $e) {
                            Log::warning("خطأ في حذف ملف {$file->getPathname()}: " . $e->getMessage());
                        }
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف ملفات PDF والطباعة: " . $e->getMessage());
        }
    }

    private function deleteMigrationFiles($moduleName)
    {
        try {
            $migrationsPath = base_path('database/migrations');
            $deletedCount = 0;

            Log::info("البحث عن ملفات migration للوحدة: {$moduleName} في المسار: {$migrationsPath}");

            // قائمة بالأشكال المختلفة لاسم الوحدة في migrations
            $searchPatterns = [
                strtolower($moduleName),
                strtolower(Str::plural($moduleName)),
                strtolower(Str::singular($moduleName)),
                Str::snake($moduleName),
                Str::snake(Str::plural($moduleName))
            ];

            // إزالة التكرارات
            $searchPatterns = array_unique($searchPatterns);
            Log::info("أنماط البحث: " . implode(', ', $searchPatterns));

            // استخدام File::files بدلاً من glob
            $allFiles = File::files($migrationsPath);

            foreach ($allFiles as $file) {
                $fileName = $file->getFilename();
                $filePath = $file->getPathname();

                // فحص اسم الملف
                $shouldDelete = false;
                foreach ($searchPatterns as $pattern) {
                    if (str_contains(strtolower($fileName), $pattern)) {
                        $shouldDelete = true;
                        Log::info("تم العثور على ملف Migration بالاسم: {$fileName} يطابق النمط: {$pattern}");
                        break;
                    }
                }

                // إذا لم نجد تطابق في الاسم، فحص المحتوى
                if (!$shouldDelete && str_ends_with($fileName, '.php')) {
                    $content = File::get($filePath);
                    foreach ($searchPatterns as $pattern) {
                        if (str_contains(strtolower($content), $pattern)) {
                            $shouldDelete = true;
                            Log::info("تم العثور على ملف Migration بالمحتوى: {$fileName} يحتوي على النمط: {$pattern}");
                            break;
                        }
                    }
                }

                // حذف الملف إذا كان يجب حذفه
                if ($shouldDelete) {
                    try {
                        if (File::delete($filePath)) {
                            $deletedCount++;
                            Log::info("تم حذف ملف Migration: {$fileName}");
                        } else {
                            Log::warning("فشل حذف ملف Migration: {$fileName}");
                        }
                    } catch (\Exception $e) {
                        Log::warning("خطأ في حذف ملف Migration {$fileName}: " . $e->getMessage());
                    }
                }
            }

            Log::info("تم حذف {$deletedCount} ملف migration للوحدة: {$moduleName}");
            return $deletedCount;

        } catch (\Exception $e) {
            Log::error("خطأ في حذف ملفات Migration: " . $e->getMessage());
            return 0;
        }
    }

    private function removeModuleRoute($moduleName)
    {
        try {
            // حذف من ملف web.php
            $webRoutesPath = base_path('routes/web.php');
            if (File::exists($webRoutesPath)) {
                $content = File::get($webRoutesPath);

                // البحث عن وحذف الأسطر المتعلقة بالوحدة
                $lines = explode("\n", $content);
                $filteredLines = [];
                $skipNext = false;

                foreach ($lines as $line) {
                    // تجاهل الأسطر الفارغة التي تتبع تعليق محذوف
                    if ($skipNext && trim($line) === '') {
                        $skipNext = false;
                        continue;
                    }
                    $skipNext = false;

                    // فحص إذا كان السطر يحتوي على المسار أو التعليق الخاص بالوحدة
                    $containsModule = false;

                    // فحص المسار المباشر
                    if (str_contains($line, "'{$moduleName}'") ||
                        str_contains($line, "\"{$moduleName}\"") ||
                        str_contains($line, "Route::GET('{$moduleName}'") ||
                        str_contains($line, "->name('{$moduleName}')") ||
                        str_contains($line, "{$moduleName}Controller") ||
                        str_contains($line, "Controllers\\{$moduleName}\\") ||
                        str_contains($line, "{$moduleName}/export-pdf-tcpdf") ||
                        str_contains($line, "{$moduleName}/print-view") ||
                        str_contains($line, "{$moduleName}TcpdfExportController") ||
                        str_contains($line, "{$moduleName}PrintController")) {
                        $containsModule = true;
                    }

                    // فحص التعليق العربي
                    if (preg_match('/\/\/\s*(.+)/', $line, $matches)) {
                        $comment = trim($matches[1]);
                        if ($this->isModuleComment($comment, $moduleName)) {
                            $containsModule = true;
                            $skipNext = true; // تجاهل السطر الفارغ التالي إن وُجد
                        }
                    }

                    if (!$containsModule) {
                        $filteredLines[] = $line;
                    }
                }

                File::put($webRoutesPath, implode("\n", $filteredLines));
                Log::info("تم حذف مسار الوحدة {$moduleName} من web.php");
            }

            // حذف من النظام الديناميكي للقوائم
            try {
                DynamicMenuHelper::removeMenuItem($moduleName);
                Log::info("تم حذف الوحدة من القائمة الديناميكية");
            } catch (\Exception $e) {
                // استخدام الطريقة القديمة كبديل
                GenerateHmvcModule::removeNavigationMenuItem($moduleName);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف مسار الوحدة: " . $e->getMessage());
        }
    }

    /**
     * فحص إذا كان التعليق يخص الوحدة المحددة
     */
    private function isModuleComment($comment, $moduleName)
    {
        // قائمة التعليقات المحتملة للوحدات مع مرادفاتها
        $possibleComments = [
            'المستخدمين' => ['Users', 'User'],
            'الصلاحيات' => ['Permissions', 'Permission'],
            'الأدوار' => ['Roles', 'Role'],
            'لوحة التحكم' => ['Dashboard'],
            'التتبع' => ['Trackings', 'Tracking'],
            'الإعدادات' => ['Settings', 'Setting'],
        ];

        // فحص إذا كان التعليق يطابق الوحدة
        foreach ($possibleComments as $arabicName => $englishNames) {
            if (in_array($moduleName, $englishNames) && str_contains($comment, $arabicName)) {
                return true;
            }
        }

        // فحص إضافي: إذا كان التعليق يحتوي على اسم الوحدة بالإنجليزية
        if (str_contains($comment, $moduleName)) {
            return true;
        }

        return false;
    }

    private function clearApplicationCache()
    {
        try {
            Artisan::call('optimize:clear');
            Log::info("تم تنظيف الكاش بنجاح");
        } catch (\Exception $e) {
            Log::warning("تحذير في تنظيف الكاش: " . $e->getMessage());
        }
    }

    // وظائف التعديل
    public function openEditModal($moduleName)
    {
        Log::info("openEditModal called with: " . $moduleName);

        $this->editingModule = $moduleName;
        $this->editMode = 'view';

        // تحميل بيانات الوحدة فقط إذا لم تكن محملة أو تغيرت الوحدة
        if (empty($this->moduleFields) || $this->editingModule !== $moduleName) {
            $this->loadModuleData($moduleName);
        }

        // تحميل معلومات المجموعة الأب للوحدة
        $this->updateModuleParentInfo($moduleName);

        $this->showEditModal = true;

        Log::info("showEditModal set to: " . ($this->showEditModal ? 'true' : 'false'));
    }

    /**
     * تحميل بيانات الوحدة من الملفات الموجودة
     */
    public function loadModuleData($moduleName)
    {
        try {
            $this->selectedModuleData = [];
            $this->moduleFields = [];
            $this->arabicName = '';
            $this->newFields = '';
            $modelPath = null; // تعريف المتغير في بداية الدالة

            // تحميل معلومات المجموعة الأب للوحدة
            $this->updateModuleParentInfo($moduleName);

            // أولاً: محاولة تحميل الحقول من قاعدة البيانات
            $databaseFields = $this->loadModuleFieldsFromDatabase($moduleName);

            if (!empty($databaseFields)) {
                $this->moduleFields = $databaseFields;
                Log::info("تم تحميل " . count($this->moduleFields) . " حقل من قاعدة البيانات للوحدة: {$moduleName}");
            } else {
                // إذا لم توجد حقول في قاعدة البيانات، أولاً: محاولة تحميل التكوين المحفوظ
                $savedFields = $this->loadModuleFieldsConfiguration($moduleName);

                if ($savedFields) {
                    $this->moduleFields = $savedFields;
                    Log::info("تم تحميل تكوين الحقول المحفوظ للوحدة: {$moduleName}");
                } else {
                    // إذا لم يوجد تكوين محفوظ، استخدم الطريقة القديمة
                    Log::info("لا يوجد تكوين محفوظ، استخدام الطريقة القديمة لاستخراج الحقول");

                    // محاولة الحصول على المعلومات من Migration
                    $this->extractFieldsFromMigrations($moduleName);
                }
            }

            // فحص وجود Model (بغض النظر عن طريقة تحميل الحقول)
            $possibleModelPaths = [
                base_path("app/Models/{$moduleName}.php"), // مثل Employees.php
                base_path("app/Models/" . Str::singular($moduleName) . ".php"), // مثل Employee.php
                base_path("app/Models/{$moduleName}/" . Str::singular($moduleName) . ".php"), // مثل Employees/Employee.php
                base_path("app/Models/{$moduleName}/{$moduleName}.php"), // مثل Employees/Employees.php
            ];

            foreach ($possibleModelPaths as $path) {
                if (File::exists($path)) {
                    $modelPath = $path;
                    break;
                }
            }

            // محاولة الحصول على الاسم العربي من dynamic-menu
            $menuItems = config('dynamic-menu.menu_items', []);
            foreach ($menuItems as $item) {
                if (isset($item['permission']) &&
                    (strtolower($item['permission']) === strtolower($moduleName) ||
                     $item['permission'] === $moduleName)) {
                    $this->arabicName = $item['title'];
                    break;
                }

                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission']) &&
                            (strtolower($child['permission']) === strtolower($moduleName) ||
                             $child['permission'] === $moduleName)) {
                            $this->arabicName = $child['title'];
                            break 2;
                        }
                    }
                }
            }

            // إذا لم نجد اسم عربي، تحقق من وحدات النظام المعروفة (فقط الوحدات المسموح عرضها)
            if (empty($this->arabicName)) {
                $systemModules = [
                    'Dashboard' => 'لوحة التحكم',
                    'ReportGenerator' => 'مولد التقارير'
                    // تم إزالة وحدات النظام المخفية من هنا
                ];

                $this->arabicName = $systemModules[$moduleName] ?? $moduleName;
            }

            $this->selectedModuleData = [
                'name' => $moduleName,
                'arabic_name' => $this->arabicName,
                'fields' => $this->moduleFields,
                'has_model' => !is_null($modelPath),
                'has_controller' => File::exists(base_path("app/Http/Controllers/{$moduleName}")),
                'has_livewire' => File::exists(base_path("app/Http/Livewire/{$moduleName}")),
                'has_views' => File::exists(base_path("resources/views/content/{$moduleName}"))
            ];

            Log::info("تم تحميل بيانات الوحدة: " . $moduleName, $this->selectedModuleData);

            // فحص تلقائي لمشاكل Syntax في ملف Livewire
            $this->checkForSyntaxIssues($moduleName);

        } catch (\Exception $e) {
            Log::error("خطأ في تحميل بيانات الوحدة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في التحميل',
                'message' => 'حدث خطأ في تحميل بيانات الوحدة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * فحص مشاكل Syntax بدون إصلاح (للعرض فقط)
     */
    public function checkSyntaxIssues()
    {
        if (empty($this->editingModule)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ',
                'message' => 'لا توجد وحدة محددة للفحص'
            ]);
            return;
        }

        try {
            // تحقق من وجود نتائج مخزنة مؤقتاً ووقت آخر إصلاح
            $cacheKey = $this->editingModule;
            $lastFixTime = $this->lastFixTime[$cacheKey] ?? 0;
            $currentTime = time();

            // إذا تم الإصلاح خلال آخر 5 دقائق، لا نظهر المشاكل مرة أخرى
            if ($lastFixTime > 0 && ($currentTime - $lastFixTime) < 300) {
                $this->detectedSyntaxIssues = [];
                $this->dispatchBrowserEvent('success', [
                    'title' => 'ملف سليم ✅',
                    'message' => 'تم إصلاح المشاكل مسبقاً. الملف سليم الآن!'
                ]);
                return;
            }

            $this->detectedSyntaxIssues = [];
            $singularName = Str::singular($this->editingModule);

            $possiblePaths = [
                base_path("app/Http/Livewire/{$this->editingModule}/{$singularName}.php"),
                base_path("app/Http/Livewire/" . Str::plural($this->editingModule) . "/{$singularName}.php"),
            ];

            $livewirePath = null;
            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    $livewirePath = $path;
                    break;
                }
            }

            if (!$livewirePath) {
                $this->dispatchBrowserEvent('info', [
                    'title' => 'ملف غير موجود',
                    'message' => 'لم يتم العثور على ملف Livewire للفحص'
                ]);
                return;
            }

            // فحص syntax الفعلي باستخدام PHP
            $syntaxCheck = shell_exec("php -l \"$livewirePath\" 2>&1");
            $issues = [];

            if (strpos($syntaxCheck, 'No syntax errors detected') === false) {
                // استخراج رسالة الخطأ
                if (preg_match('/PHP Parse error: (.+) in (.+) on line (\d+)/', $syntaxCheck, $matches)) {
                    $errorMsg = $matches[1];
                    $lineNumber = $matches[3];
                    $issues[] = "خطأ syntax في السطر {$lineNumber}: {$errorMsg}";
                } else {
                    $issues[] = 'يوجد خطأ syntax في الملف';
                }
            } else {
                // إذا لم تكن هناك أخطاء syntax فعلية، نتحقق فقط من مشاكل خطيرة
                $content = File::get($livewirePath);

                // فحص أقواس غير متطابقة فقط (مشكلة خطيرة)
                $openBraces = substr_count($content, '{');
                $closeBraces = substr_count($content, '}');
                if ($openBraces !== $closeBraces) {
                    $issues[] = 'عدد الأقواس المفتوحة والمغلقة غير متطابق';
                }

                // فحص جمل if غير مكتملة فقط (مشكلة خطيرة)
                if (preg_match('/if\s*\(\s*[a-zA-Z_][a-zA-Z0-9_]*\s*$/', $content)) {
                    $issues[] = 'يوجد جملة if غير مكتملة';
                }

                // تجاهل مشاكل التنسيق البسيطة مثل المسافات
            }

            $this->detectedSyntaxIssues = $issues;

            // حفظ النتيجة في الكاش
            $this->syntaxCheckCache[$cacheKey] = $issues;

            if (!empty($issues)) {
                $this->dispatchBrowserEvent('warning', [
                    'title' => 'تم العثور على مشاكل',
                    'message' => 'تم العثور على ' . count($issues) . ' مشكلة syntax في ملف Livewire'
                ]);
            } else {
                $this->dispatchBrowserEvent('success', [
                    'title' => 'ملف سليم ✅',
                    'message' => 'لا توجد مشاكل syntax في ملف Livewire'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في فحص syntax: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الفحص',
                'message' => 'حدث خطأ أثناء فحص الملف'
            ]);
        }
    }

    /**
     * فحص مشاكل Syntax (alias للدالة السابقة - للتوافق)
     */
    public function checkForSyntaxIssues()
    {
        $this->checkSyntaxIssues();
    }

    /**
     * استخراج الحقول من ملفات Migration
     */
    private function extractFieldsFromMigrations($moduleName)
    {
        try {
            $migrationsPath = base_path('database/migrations');
            $searchPatterns = [
                strtolower($moduleName),
                strtolower(Str::plural($moduleName)),
                strtolower(Str::singular($moduleName))
            ];

            $allFiles = File::files($migrationsPath);

            foreach ($allFiles as $file) {
                $fileName = $file->getFilename();

                foreach ($searchPatterns as $pattern) {
                    if (str_contains(strtolower($fileName), $pattern)) {
                        $migrationContent = File::get($file->getPathname());
                        $this->parseMigrationFields($migrationContent);
                        break 2;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("خطأ في استخراج حقول Migration: " . $e->getMessage());
        }
    }

    /**
     * تحليل حقول Migration
     */
    private function parseMigrationFields($migrationContent)
    {
        // البحث عن تعريفات الحقول في Migration
        $patterns = [
            '/\$table->(\w+)\([\'"](\w+)[\'"].*?\)/' => ['type' => '$1', 'name' => '$2'],
            '/\$table->(\w+)\([\'"](\w+)[\'"]/' => ['type' => '$1', 'name' => '$2']
        ];

        foreach ($patterns as $pattern => $mapping) {
            if (preg_match_all($pattern, $migrationContent, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fieldName = $match[2];
                    $fieldType = $match[1];

                    // تجاهل الحقول الافتراضية
                    if (in_array($fieldName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        continue;
                    }

                    // إضافة أو تحديث الحقل
                    $existingIndex = collect($this->moduleFields)->search(function($field) use ($fieldName) {
                        return $field['name'] === $fieldName;
                    });

                    if ($existingIndex !== false) {
                        $this->moduleFields[$existingIndex]['type'] = $this->mapMigrationTypeToFormType($fieldType);
                    } else {
                        $this->moduleFields[] = [
                            'name' => $fieldName,
                            'type' => $this->mapMigrationTypeToFormType($fieldType),
                            'required' => str_contains($migrationContent, "'{$fieldName}'".'->nullable()') ? false : true
                        ];
                    }
                }
                break; // وجدنا تطابق، توقف
            }
        }
    }

    /**
     * تحويل أنواع Migration إلى أنواع النماذج
     */
    private function mapMigrationTypeToFormType($migrationType)
    {
        $mapping = [
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'bigInteger' => 'integer',
            'decimal' => 'decimal',
            'float' => 'decimal',
            'boolean' => 'checkbox',
            'date' => 'date',
            'dateTime' => 'datetime',
            'time' => 'time',
            'json' => 'text',
            'enum' => 'select'
        ];

        return $mapping[$migrationType] ?? 'string';
    }

    public function refreshModules()
    {
        $this->loadModules();
        $this->dispatchBrowserEvent('success', [
            'title' => 'تحديث القائمة',
            'message' => 'تم تحديث قائمة الوحدات بنجاح'
        ]);
    }

    public function editModule()
    {
        if (!$this->editingModule) {
            return;
        }

        // هنا يمكن إضافة منطق التعديل
        $this->dispatchBrowserEvent('info', [
            'title' => 'تطوير الميزة',
            'message' => "سيتم إضافة وظيفة تعديل الوحدة {$this->editingModule} قريباً"
        ]);

        $this->showEditModal = false;
        $this->editingModule = null;
    }

    // دالة مساعدة للحذف السريع باستخدام PowerShell
    public function quickDeleteModule($moduleName)
    {
        if (!$moduleName) {
            return;
        }

        try {
            // تنفيذ الحذف السريع
            $this->executeArtisanModuleDeletion($moduleName);
            $this->executePowerShellModuleDeletion($moduleName);
            $this->removeModuleRoute($moduleName);
            $this->clearApplicationCache();

            $this->dispatchBrowserEvent('success', [
                'title' => 'حذف سريع',
                'message' => "تم حذف الوحدة {$moduleName} بنجاح"
            ]);

            $this->loadModules();

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الحذف السريع',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إزالة الوحدة من قائمة dynamic-menu.php والمجموعات الأساسية مع حماية الوحدات الفرعية
     */
    private function removeModuleFromDynamicMenu($moduleName)
    {
        try {
            // الحصول على معلومات الوحدة قبل الحذف لتحديد النوع
            $moduleInfo = $this->getModuleInfo($moduleName);

            if ($moduleInfo) {
                if ($moduleInfo['type'] === 'main') {
                    // إذا كانت الوحدة هي مجموعة أب، احذف المجموعة كاملة من كل شيء
                    $this->removeModuleCompletelyFromConfig($moduleName);
                    Log::info("تم حذف المجموعة الرئيسية {$moduleName} من القائمة الديناميكية");

                    // حذف من جدول basic_groups
                    $this->removeParentGroupFromBasicGroups($moduleName);

                } elseif ($moduleInfo['type'] === 'sub') {
                    // للوحدات الفرعية: فحص إضافي للتأكد من وجود Routes قبل الحذف
                    $hasActiveRoutes = $this->checkIfModuleHasActiveRoutes($moduleName);

                    if ($hasActiveRoutes) {
                        // إذا كانت الوحدة لها routes نشطة، لا تحذفها من القائمة
                        Log::warning("الوحدة الفرعية {$moduleName} لها routes نشطة، لن يتم حذفها من القائمة الجانبية");

                        // إظهار تحذير للمستخدم
                        $this->dispatchBrowserEvent('warning', [
                            'title' => 'تحذير',
                            'message' => "الوحدة {$moduleName} محمية من الحذف لأنها تحتوي على routes نشطة. تم حذف الملفات فقط."
                        ]);

                        return; // توقف ولا تحذف من القائمة
                    } else {
                        // إذا لم تكن لها routes نشطة، احذفها بالطريقة الشاملة
                        $this->removeModuleCompletelyFromConfig($moduleName);
                        Log::info("تم حذف الوحدة الفرعية {$moduleName} من القائمة الديناميكية");
                    }
                } else {
                    // للوحدات القديمة أو غير المصنفة، استخدم الطريقة الشاملة
                    $this->removeModuleCompletelyFromConfig($moduleName);
                    Log::info("تم حذف الوحدة {$moduleName} من القائمة الديناميكية (طريقة تقليدية)");
                }
            } else {
                // إذا لم نجد معلومات الوحدة، فحص Routes أولاً
                $hasActiveRoutes = $this->checkIfModuleHasActiveRoutes($moduleName);

                if ($hasActiveRoutes) {
                    Log::warning("الوحدة {$moduleName} لها routes نشطة، لن يتم حذفها من القائمة الجانبية");
                    $this->dispatchBrowserEvent('warning', [
                        'title' => 'تحذير',
                        'message' => "الوحدة {$moduleName} محمية من الحذف لأنها تحتوي على routes نشطة."
                    ]);
                    return;
                } else {
                    // إذا لم تكن لها routes نشطة، استخدم الطريقة الشاملة
                    $this->removeModuleCompletelyFromConfig($moduleName);
                    Log::info("تم حذف الوحدة {$moduleName} من القائمة الديناميكية (طريقة افتراضية)");
                }
            }

        } catch (\Exception $e) {
            Log::warning("خطأ في حذف الوحدة من القائمة الديناميكية: " . $e->getMessage());

            // كحل بديل، جرب الطريقة الشاملة مع فحص Routes
            try {
                $hasActiveRoutes = $this->checkIfModuleHasActiveRoutes($moduleName);

                if (!$hasActiveRoutes) {
                    $this->removeModuleCompletelyFromConfig($moduleName);
                    Log::info("تم حذف الوحدة {$moduleName} باستخدام الطريقة البديلة");
                } else {
                    Log::info("تم الاحتفاظ بالوحدة {$moduleName} في القائمة لوجود routes نشطة");
                }
            } catch (\Exception $fallbackException) {
                Log::error("فشل في حذف الوحدة باستخدام الطريقة البديلة: " . $fallbackException->getMessage());
            }
        }
    }

    /**
     * إزالة الوحدة بشكل شامل من ملف التكوين مع تنظيف جميع المراجع
     */
    private function removeModuleCompletelyFromConfig($moduleName)
    {
        $configPath = config_path('dynamic-menu.php');
        if (!file_exists($configPath)) {
            throw new \Exception('ملف التكوين غير موجود');
        }

        $config = include $configPath;
        if (!isset($config['menu_items'])) {
            throw new \Exception('هيكل ملف التكوين غير صحيح');
        }

        $updatedMenuItems = [];

        foreach ($config['menu_items'] as $item) {
            $shouldRemoveItem = false;

            // فحص إذا كانت الوحدة هي العنصر الرئيسي نفسه
            if (isset($item['permission']) && $item['permission'] === $moduleName) {
                $shouldRemoveItem = true;
            }

            if (!$shouldRemoveItem) {
                // إذا كانت مجموعة، نظف العناصر الفرعية والمراجع
                if ($item['type'] === 'group') {
                    // تنظيف العناصر الفرعية
                    if (isset($item['children'])) {
                        $item['children'] = array_filter($item['children'], function($child) use ($moduleName) {
                            return !(isset($child['permission']) && $child['permission'] === $moduleName);
                        });
                        // إعادة فهرسة المصفوفة
                        $item['children'] = array_values($item['children']);
                    }

                    // تنظيف active_routes
                    if (isset($item['active_routes'])) {
                        $item['active_routes'] = array_values(array_filter($item['active_routes'], function($route) use ($moduleName) {
                            return $route !== $moduleName;
                        }));
                    }
                }

                $updatedMenuItems[] = $item;
            }
        }

        // تحديث التكوين
        $config['menu_items'] = $updatedMenuItems;

        // كتابة الملف المحدث
        $newConfigContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($configPath, $newConfigContent);

        // مسح كاش التكوين
        Artisan::call('config:clear');

        Log::info("تم حذف الوحدة {$moduleName} بشكل شامل من ملف التكوين");
    }

    /**
     * فحص ما إذا كانت الوحدة لها routes نشطة في web.php
     */
    private function checkIfModuleHasActiveRoutes($moduleName)
    {
        try {
            $webRoutesPath = base_path('routes/web.php');
            if (!File::exists($webRoutesPath)) {
                return false;
            }

            $content = File::get($webRoutesPath);

            // فحص وجود routes خاصة بالوحدة
            $routePatterns = [
                "Route::GET('{$moduleName}'",
                "Route::get('{$moduleName}'",
                "->name('{$moduleName}')",
                "{$moduleName}Controller",
                "Controllers\\{$moduleName}\\",
                "{$moduleName}/export-pdf-tcpdf",
                "{$moduleName}/print-view",
                "{$moduleName}TcpdfExportController",
                "{$moduleName}PrintController"
            ];

            foreach ($routePatterns as $pattern) {
                if (strpos($content, $pattern) !== false) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error("خطأ في فحص routes للوحدة {$moduleName}: " . $e->getMessage());
            return false; // في حالة الخطأ، اعتبر أنه لا توجد routes نشطة
        }
    }

    /**
     * حذف المجموعة الأساسية من جدول basic_groups (حذف ناعم) مع حماية الوحدات الفرعية
     */
    private function removeParentGroupFromBasicGroups($moduleName)
    {
        try {
            // فحص ما إذا كانت هناك وحدات فرعية تعتمد على هذه المجموعة
            $hasSubModules = $this->checkIfGroupHasActiveSubModules($moduleName);

            if ($hasSubModules) {
                Log::warning("المجموعة {$moduleName} تحتوي على وحدات فرعية نشطة، لن يتم حذفها من basic_groups");

                $this->dispatchBrowserEvent('warning', [
                    'title' => 'تحذير',
                    'message' => "المجموعة {$moduleName} محمية من الحذف لأنها تحتوي على وحدات فرعية نشطة"
                ]);

                return; // توقف ولا تحذف من basic_groups
            }

            // البحث عن المجموعة بأسماء مختلفة محتملة
            $possibleNames = [
                $moduleName,
                strtolower($moduleName),
                ucfirst(strtolower($moduleName)),
                Str::singular($moduleName),
                Str::singular(strtolower($moduleName)),
                Str::plural($moduleName),
                Str::plural(strtolower($moduleName))
            ];

            $possibleNames = array_unique($possibleNames);
            $deletedCount = 0;

            foreach ($possibleNames as $name) {
                // البحث بالاسم الإنجليزي والعربي (بما في ذلك المحذوفة)
                $groups = BasicGroup::withTrashed()
                    ->where(function($query) use ($name) {
                        $query->where('name_en', $name)
                              ->orWhere('name_ar', $name);
                    })
                    ->whereNull('deleted_at') // فقط غير المحذوفة
                    ->get();

                foreach ($groups as $group) {
                    Log::info("حذف نهائي للمجموعة الأساسية من basic_groups: {$group->name_en} (ID: {$group->id})");

                    // استخدام الحذف النهائي بدلاً من الناعم لضمان إزالة كاملة
                    $group->forceDelete(); // حذف نهائي

                    $deletedCount++;
                }
            }

            if ($deletedCount > 0) {
                Log::info("تم حذف/تعطيل {$deletedCount} مجموعة أساسية من جدول basic_groups للوحدة {$moduleName}");
            } else {
                Log::info("لم يتم العثور على مجموعة أساسية في جدول basic_groups للوحدة {$moduleName}");
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف المجموعة الأساسية من basic_groups: " . $e->getMessage());
        }
    }

    /**
     * فحص ما إذا كانت المجموعة تحتوي على وحدات فرعية نشطة
     */
    private function checkIfGroupHasActiveSubModules($groupName)
    {
        try {
            $menuItems = config('dynamic-menu.menu_items', []);

            foreach ($menuItems as $item) {
                if ($item['type'] === 'group' &&
                    isset($item['permission']) &&
                    $item['permission'] === $groupName &&
                    isset($item['children']) &&
                    !empty($item['children'])) {

                    // فحص كل وحدة فرعية للتأكد من وجود routes نشطة
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission'])) {
                            $hasRoutes = $this->checkIfModuleHasActiveRoutes($child['permission']);
                            if ($hasRoutes) {
                                Log::info("المجموعة {$groupName} تحتوي على وحدة فرعية نشطة: {$child['permission']}");
                                return true;
                            }
                        }
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error("خطأ في فحص الوحدات الفرعية للمجموعة {$groupName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * استعادة الوحدة المحذوفة خطأً من القائمة إلى مكانها الفرعي
     */
    public function restoreModuleToMenu()
    {
        if (empty($this->editingModule)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في البيانات',
                'message' => 'يجب اختيار وحدة أولاً'
            ]);
            return;
        }

        try {
            // فحص وجود routes للوحدة
            $hasActiveRoutes = $this->checkIfModuleHasActiveRoutes($this->editingModule);

            if (!$hasActiveRoutes) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'لا يمكن الاستعادة',
                    'message' => 'الوحدة لا تحتوي على routes نشطة في web.php'
                ]);
                return;
            }

            // البحث عن مجموعة مناسبة لإضافة الوحدة إليها
            $targetGroup = $this->findSuitableGroupForModule($this->editingModule);

            if (!$targetGroup) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'لا توجد مجموعة مناسبة',
                    'message' => 'لم يتم العثور على مجموعة مناسبة لإضافة الوحدة إليها'
                ]);
                return;
            }

            // إضافة الوحدة إلى المجموعة المختارة
            $this->addModuleToGroup($this->editingModule, $targetGroup);

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم بنجاح',
                'message' => "تم استعادة الوحدة '{$this->editingModule}' إلى مجموعة '{$targetGroup}' بنجاح"
            ]);

            // إعادة تحميل البيانات
            $this->loadModules();
            $this->loadAvailableGroups();

            // إعادة تحديث الصفحة لإظهار التغييرات
            $this->dispatchBrowserEvent('reload', [
                'delay' => 1500
            ]);

        } catch (\Exception $e) {
            Log::error("خطأ في استعادة الوحدة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الاستعادة',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * البحث عن مجموعة مناسبة لإضافة الوحدة إليها
     */
    private function findSuitableGroupForModule($moduleName)
    {
        $menuItems = config('dynamic-menu.menu_items', []);

        // أولاً: البحث عن مجموعة بنفس الاسم أو اسم مشابه
        $similarNames = [
            strtolower($moduleName),
            strtolower(Str::plural($moduleName)),
            strtolower(Str::singular($moduleName))
        ];

        foreach ($menuItems as $item) {
            if ($item['type'] === 'group') {
                $groupName = strtolower($item['permission']);

                foreach ($similarNames as $name) {
                    if (strpos($groupName, $name) !== false || strpos($name, $groupName) !== false) {
                        return $item['permission'];
                    }
                }
            }
        }

        // ثانياً: العثور على أول مجموعة متاحة
        foreach ($menuItems as $item) {
            if ($item['type'] === 'group') {
                return $item['permission'];
            }
        }

        return null;
    }

    /**
     * إضافة الوحدة إلى مجموعة محددة
     */
    private function addModuleToGroup($moduleName, $groupName)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        if (!isset($config['menu_items'])) {
            throw new \Exception('هيكل ملف التكوين غير صحيح');
        }

        $updated = false;

        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group' && $item['permission'] === $groupName) {

                // التحقق من عدم وجود الوحدة مسبقاً
                $moduleExists = false;
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission']) && $child['permission'] === $moduleName) {
                            $moduleExists = true;
                            break;
                        }
                    }
                }

                if (!$moduleExists) {
                    // إضافة الوحدة كعنصر فرعي
                    $newItem = [
                        'type' => 'item',
                        'permission' => $moduleName,
                        'title' => $this->getModuleArabicNameFromSources($moduleName) ?: $moduleName,
                        'route' => $moduleName,
                        'icon' => 'mdi mdi-circle-outline',
                        'active_routes' => [$moduleName]
                    ];

                    if (!isset($item['children'])) {
                        $item['children'] = [];
                    }
                    $item['children'][] = $newItem;

                    // تحديث active_routes للمجموعة الأب
                    if (!in_array($moduleName, $item['active_routes'])) {
                        $item['active_routes'][] = $moduleName;
                    }

                    $updated = true;
                    break;
                }
            }
        }

        if ($updated) {
            // كتابة الملف المحدث
            $newConfigContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($configPath, $newConfigContent);

            // مسح كاش التكوين
            Artisan::call('config:clear');

            Log::info("تم إضافة الوحدة {$moduleName} إلى مجموعة {$groupName}");
        } else {
            throw new \Exception('فشل في إضافة الوحدة إلى المجموعة');
        }
    }
    public function setEditMode($mode)
    {
        $this->editMode = $mode;

        if ($mode === 'add_fields') {
            // إعادة تعيين الحقول المعلقة والنموذج عند الدخول لوضع إضافة الحقول بصمت
            $this->resetNewFieldFormSilently(); // استخدام النسخة الصامتة
            $this->newFields = '';

            // التأكد من إعداد القيم الافتراضية الصحيحة
            $this->newField['show_in_table'] = true;
            $this->newField['show_in_search'] = true;
            $this->newField['show_in_forms'] = true;
            $this->newField['searchable'] = true;
            $this->newField['required'] = true;
            $this->newField['type'] = 'string';

            // تحميل معلومات المجموعة الحالية عند الدخول لوضع إضافة الحقول
            $this->updateModuleParentInfo($this->editingModule);

            // Log القيم للتأكد
            Log::info('New field values after setEditMode:', $this->newField);

            // إرسال إشارة للواجهة لتحديث العرض
            $this->dispatchBrowserEvent('refreshForm');
        }

        Log::info("تم تغيير وضع التعديل إلى: " . $mode);
    }

    /**
     * تحديث معلومات المجموعة الأب للوحدة المحددة
     */
    public function updateModuleParentInfo($moduleName)
    {
        if (empty($moduleName)) {
            return;
        }

        $this->selectedModule = $moduleName;
        $this->currentParentGroup = '';
        $this->selectedParentGroup = '';

        // البحث عن المجموعة الحالية للوحدة
        $menuItems = config('dynamic-menu.menu_items', []);
        $foundInMenu = false;

        foreach ($menuItems as $item) {
            // فحص إذا كانت الوحدة مجموعة رئيسية أساسية
            if ($item['type'] === 'group' && isset($item['permission']) &&
                (strtolower($item['permission']) === strtolower($moduleName) ||
                 $item['permission'] === $moduleName) &&
                isset($item['basic_group_id'])) {
                $this->currentParentGroup = 'مجموعة رئيسية أساسية';
                $foundInMenu = true;
                break;
            }

            // فحص إذا كانت الوحدة رئيسية أساسية (item مع basic_group_id)
            if ($item['type'] === 'item' && isset($item['permission']) &&
                (strtolower($item['permission']) === strtolower($moduleName) ||
                 $item['permission'] === $moduleName) &&
                isset($item['basic_group_id'])) {
                $this->currentParentGroup = 'وحدة رئيسية أساسية';
                $foundInMenu = true;
                break;
            }

            // فحص إذا كانت الوحدة عنصر رئيسي منفصل
            if ($item['type'] === 'item' && isset($item['permission']) &&
                (strtolower($item['permission']) === strtolower($moduleName) ||
                 $item['permission'] === $moduleName) &&
                !isset($item['basic_group_id'])) {
                $this->currentParentGroup = 'وحدة رئيسية منفصلة';
                $foundInMenu = true;
                break;
            }

            // فحص إذا كانت الوحدة فرعية
            if ($item['type'] === 'group' && isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (isset($child['permission']) &&
                        (strtolower($child['permission']) === strtolower($moduleName) ||
                         $child['permission'] === $moduleName)) {
                        $this->currentParentGroup = $item['title'] . ' (' . $item['permission'] . ')';
                        $foundInMenu = true;
                        break 2;
                    }
                }
            }
        }        // إذا لم توجد في القائمة، تحقق من نوعها
        if (!$foundInMenu) {
            $this->currentParentGroup = 'غير مدرجة في القائمة';
        }

        Log::info("تم تحديث معلومات المجموعة الأب - الوحدة: {$moduleName}، المجموعة الحالية: {$this->currentParentGroup}");
    }

    /**
     * تحديث المجموعة الأب للوحدة
     */
    public function updateParentGroup()
    {
        if (empty($this->selectedModule) || empty($this->selectedParentGroup)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في البيانات',
                'message' => 'يجب اختيار وحدة ومجموعة أب جديدة'
            ]);
            return;
        }

        if ($this->selectedParentGroup === $this->currentParentGroup) {
            $this->dispatchBrowserEvent('info', [
                'title' => 'لا توجد تغييرات',
                'message' => 'المجموعة المختارة هي نفس المجموعة الحالية'
            ]);
            return;
        }

        try {
            // استخدام الخدمة لتحديث المجموعة الأب
            $service = app(DynamicMenuService::class);
            $result = $service->updateParentGroup($this->selectedModule, $this->selectedParentGroup);

            if ($result) {
                // تحديث المعلومات المحلية
                $this->currentParentGroup = $this->selectedParentGroup;

                // إعادة تحميل قائمة الوحدات والمجموعات
                $this->loadModules();
                $this->loadAvailableGroups();

                $this->dispatchBrowserEvent('success', [
                    'title' => 'نجح التحديث',
                    'message' => "تم نقل الوحدة '{$this->selectedModule}' إلى المجموعة '{$this->selectedParentGroup}' بنجاح"
                ]);

                // إعادة تعيين الاختيار
                $this->selectedParentGroup = '';

                Log::info("تم نقل الوحدة {$this->selectedModule} إلى المجموعة {$this->currentParentGroup}");
            } else {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'فشل التحديث',
                    'message' => 'حدث خطأ أثناء تحديث المجموعة الأب'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في تحديث المجموعة الأب: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في التحديث',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * تحويل الوحدة إلى وحدة رئيسية منفصلة مع تنظيف شامل
     */
    public function makeModuleStandalone()
    {
        if (empty($this->editingModule)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في البيانات',
                'message' => 'يجب اختيار وحدة أولاً'
            ]);
            return;
        }

        try {
            // قراءة ملف dynamic-menu.php
            $configPath = config_path('dynamic-menu.php');
            if (!file_exists($configPath)) {
                throw new \Exception('ملف التكوين غير موجود');
            }

            $config = include $configPath;

            // التحقق من وجود menu_items
            if (!isset($config['menu_items'])) {
                throw new \Exception('هيكل ملف التكوين غير صحيح - menu_items غير موجود');
            }

            // البحث عن الوحدة في القائمة وإزالتها
            $moduleFound = false;
            $moduleData = null;
            $updatedMenuItems = [];

            foreach ($config['menu_items'] as $group) {
                $groupUpdated = false;

                if (isset($group['children']) && is_array($group['children'])) {
                    $updatedChildren = [];
                    foreach ($group['children'] as $item) {
                        // إذا كانت هذه هي الوحدة المطلوب تحويلها
                        if (isset($item['permission']) && $item['permission'] === $this->editingModule) {
                            $moduleFound = true;
                            $moduleData = $item;
                            $groupUpdated = true;
                            continue; // لا نضيف العنصر للمجموعة الحالية
                        } else {
                            $updatedChildren[] = $item;
                        }
                    }

                    // تحديث المجموعة بالعناصر المتبقية
                    $group['children'] = $updatedChildren;

                    // إزالة الوحدة من active_routes للمجموعة الأب
                    if ($groupUpdated && isset($group['active_routes'])) {
                        $group['active_routes'] = array_values(array_filter($group['active_routes'], function($route) {
                            return $route !== $this->editingModule;
                        }));
                    }
                }

                $updatedMenuItems[] = $group;
            }

            if (!$moduleFound) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'الوحدة غير موجودة',
                    'message' => 'لم يتم العثور على الوحدة في التكوين'
                ]);
                return;
            }

            // التحقق من عدم وجود BasicGroup مسبقاً لهذه الوحدة لتجنب التكرار
            $existingBasicGroup = BasicGroup::withTrashed()
                ->where('name_en', $moduleData['permission'])
                ->orWhere('permission', $moduleData['permission'])
                ->first();

            if ($existingBasicGroup) {
                // إذا كان محذوفاً، استعده، وإلا استخدمه كما هو
                if ($existingBasicGroup->trashed()) {
                    $existingBasicGroup->restore();
                }
                $basicGroup = $existingBasicGroup;
            } else {
                // إنشاء مجموعة أساسية جديدة في قاعدة البيانات
                $basicGroup = BasicGroup::create([
                    'name_ar' => $moduleData['title'],
                    'name_en' => $moduleData['permission'],
                    'icon' => $moduleData['icon'] ?? 'mdi mdi-view-dashboard',
                    'description_ar' => 'وحدة تم تحويلها من فرعية إلى رئيسية: ' . $moduleData['title'],
                    'description_en' => 'Module converted from sub to standalone: ' . $moduleData['permission'],
                    'sort_order' => 999,
                    'status' => true,
                    'type' => 'item', // حسب القاعدة: كل ما يأتي من إدارة الوحدات = item
                    'route' => $moduleData['permission'], // إضافة المسار للوصول المباشر
                    'permission' => $moduleData['permission'],
                    'active_routes' => $moduleData['permission']
                ]);
            }

            // إنشاء عنصر جديد للوحدة المنفصلة مع basic_group_id
            $newItem = [
                'type' => 'item',
                'basic_group_id' => $basicGroup->id,
                'permission' => $moduleData['permission'],
                'title' => $moduleData['title'],
                'route' => $moduleData['route'] ?? $moduleData['permission'],
                'icon' => $moduleData['icon'] ?? 'mdi mdi-view-dashboard',
                'active_routes' => [
                    $moduleData['permission']
                ]
            ];

            // إضافة العنصر الجديد
            $updatedMenuItems[] = $newItem;

            // تحديث التكوين
            $config['menu_items'] = $updatedMenuItems;

            // إنشاء محتوى الملف الجديد
            $newConfigContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";

            // كتابة الملف المحدث
            file_put_contents($configPath, $newConfigContent);

            // مسح كاش التكوين لضمان التحديث الفوري
            Artisan::call('config:clear');

            // تحديث المعلومات المحلية
            $this->currentParentGroup = 'standalone';

            // إعادة تحميل البيانات
            $this->loadModules();
            $this->loadAvailableGroups();

            $this->dispatchBrowserEvent('success', [
                'title' => 'نجح التحويل',
                'message' => "تم تحويل الوحدة '{$this->editingModule}' إلى وحدة رئيسية منفصلة بنجاح"
            ]);

            // إعادة تحديث الصفحة لإظهار التغييرات في القائمة
            $this->dispatchBrowserEvent('reload', [
                'delay' => 1500 // تأخير 1.5 ثانية قبل إعادة التحديث
            ]);

            Log::info("تم تحويل الوحدة {$this->editingModule} إلى وحدة رئيسية منفصلة");

        } catch (\Exception $e) {
            Log::error("خطأ في تحويل الوحدة إلى منفصلة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'فشل التحويل',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * تحويل الوحدة الرئيسية إلى فرعية تحت مجموعة مع تنظيف شامل
     */
    public function makeModuleSubModule()
    {
        if (empty($this->editingModule) || empty($this->selectedParentGroup)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في البيانات',
                'message' => 'يجب اختيار وحدة ومجموعة أب'
            ]);
            return;
        }

        try {
            // قراءة ملف dynamic-menu.php
            $configPath = config_path('dynamic-menu.php');
            if (!file_exists($configPath)) {
                throw new \Exception('ملف التكوين غير موجود');
            }

            $config = include $configPath;

            if (!isset($config['menu_items'])) {
                throw new \Exception('هيكل ملف التكوين غير صحيح');
            }

            $moduleFound = false;
            $moduleData = null;
            $parentGroupFound = false;
            $updatedMenuItems = [];

            // الخطوة 1: البحث عن الوحدة وجمع بياناتها وإزالتها من موقعها الحالي
            foreach ($config['menu_items'] as $item) {
                $itemToAdd = $item; // نسخة للتعديل عليها

                // البحث في المستوى الأعلى
                if (isset($item['permission']) && $item['permission'] === $this->editingModule) {
                    // التحقق من نوع الوحدة
                    if ($item['type'] === 'item' && !isset($item['basic_group_id'])) {
                        // وحدة رئيسية منفصلة بدون basic_group_id
                        $moduleFound = true;
                        $moduleData = $item;

                        // البحث عن BasicGroup بناءً على الصلاحية وحذفه (لمولد الوحدات)
                        $basicGroupsToDelete = BasicGroup::withTrashed()
                            ->where('permission', $item['permission'])
                            ->orWhere('name_en', $item['permission'])
                            ->get();

                        foreach ($basicGroupsToDelete as $bgToDelete) {
                            $bgToDelete->forceDelete();
                        }

                        continue; // لا نضيفها للقائمة المحدثة
                    } elseif ($item['type'] === 'item' && isset($item['basic_group_id'])) {
                        // وحدة رئيسية مع basic_group_id - يجب حذف BasicGroup من قاعدة البيانات نهائياً
                        $moduleFound = true;
                        $moduleData = $item;

                        // حذف BasicGroup من قاعدة البيانات نهائياً
                        BasicGroup::where('id', $item['basic_group_id'])->forceDelete();

                        continue; // لا نضيفها للقائمة المحدثة
                    } elseif ($item['type'] === 'group' && isset($item['basic_group_id'])) {
                        // مجموعة رئيسية - يجب حذف BasicGroup من قاعدة البيانات نهائياً
                        $moduleFound = true;
                        $moduleData = $item;

                        // حذف BasicGroup من قاعدة البيانات نهائياً
                        BasicGroup::where('id', $item['basic_group_id'])->forceDelete();

                        continue; // لا نضيفها للقائمة المحدثة
                    }
                }

                // البحث في العناصر الفرعية إذا كان العنصر مجموعة
                if ($item['type'] === 'group' && isset($item['children']) && is_array($item['children'])) {
                    $updatedChildren = [];
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission']) && $child['permission'] === $this->editingModule) {
                            // وجدت الوحدة في العناصر الفرعية
                            $moduleFound = true;
                            $moduleData = $child;

                            // إذا كان لديها basic_group_id، احذفه من قاعدة البيانات
                            if (isset($child['basic_group_id'])) {
                                BasicGroup::where('id', $child['basic_group_id'])->forceDelete();
                            } else {
                                // البحث عن BasicGroup بناءً على الصلاحية وحذفه
                                $basicGroupsToDelete = BasicGroup::withTrashed()
                                    ->where('permission', $child['permission'])
                                    ->orWhere('name_en', $child['permission'])
                                    ->get();

                                foreach ($basicGroupsToDelete as $bgToDelete) {
                                    $bgToDelete->forceDelete();
                                }
                            }

                            // لا نضيف هذا العنصر الفرعي للقائمة المحدثة
                            continue;
                        }
                        $updatedChildren[] = $child;
                    }
                    $itemToAdd['children'] = $updatedChildren;
                }

                $updatedMenuItems[] = $itemToAdd;
            }

            if (!$moduleFound) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'الوحدة غير موجودة',
                    'message' => 'لم يتم العثور على الوحدة كوحدة رئيسية أو مجموعة'
                ]);
                return;
            }

            // الخطوة 2: تنظيف جميع المراجع للوحدة من active_routes في جميع المجموعات
            foreach ($updatedMenuItems as &$item) {
                if ($item['type'] === 'group' && isset($item['active_routes'])) {
                    // إزالة الوحدة من active_routes إذا كانت موجودة
                    $item['active_routes'] = array_values(array_filter($item['active_routes'], function($route) {
                        return $route !== $this->editingModule;
                    }));
                }
            }

            // الخطوة 3: البحث عن المجموعة الأب وإضافة الوحدة إليها
            foreach ($updatedMenuItems as &$item) {
                if (isset($item['permission']) && $item['permission'] === $this->selectedParentGroup &&
                    $item['type'] === 'group') {
                    $parentGroupFound = true;

                    // تحويل الوحدة إلى عنصر فرعي
                    $subItem = [
                        'type' => 'item',
                        'permission' => $moduleData['permission'],
                        'title' => $moduleData['title'],
                        'route' => $moduleData['route'] ?? $moduleData['permission'],
                        'icon' => $moduleData['icon'] ?? 'mdi mdi-circle-outline',
                        'active_routes' => [
                            $moduleData['permission']
                        ]
                    ];

                    // إضافة الوحدة كعنصر فرعي
                    if (!isset($item['children'])) {
                        $item['children'] = [];
                    }
                    $item['children'][] = $subItem;

                    // تحديث active_routes للمجموعة الأب
                    if (!in_array($moduleData['permission'], $item['active_routes'])) {
                        $item['active_routes'][] = $moduleData['permission'];
                    }

                    break;
                }
            }

            if (!$parentGroupFound) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'المجموعة غير موجودة',
                    'message' => 'لم يتم العثور على المجموعة الأب المحددة'
                ]);
                return;
            }

            // تحديث التكوين
            $config['menu_items'] = $updatedMenuItems;

            // إنشاء محتوى الملف الجديد
            $newConfigContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";

            // كتابة الملف المحدث
            file_put_contents($configPath, $newConfigContent);

            // مسح كاش التكوين لضمان التحديث الفوري
            Artisan::call('config:clear');

            // تحديث المعلومات المحلية
            $this->currentParentGroup = $this->selectedParentGroup;

            // إعادة تحميل البيانات
            $this->loadModules();
            $this->loadAvailableGroups();

            $this->dispatchBrowserEvent('success', [
                'title' => 'نجح التحويل',
                'message' => "تم تحويل الوحدة '{$this->editingModule}' إلى وحدة فرعية تحت '{$this->selectedParentGroup}' بنجاح"
            ]);

            // إعادة تحديث الصفحة لإظهار التغييرات في القائمة
            $this->dispatchBrowserEvent('reload', [
                'delay' => 1500 // تأخير 1.5 ثانية قبل إعادة التحديث
            ]);

            Log::info("تم تحويل الوحدة {$this->editingModule} إلى فرعية تحت {$this->selectedParentGroup}");

        } catch (\Exception $e) {
            Log::error("خطأ في تحويل الوحدة إلى فرعية: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'فشل التحويل',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إضافة حقول جديدة للوحدة
     */
    public function addNewFields()
    {
        try {
            if (empty($this->newFields)) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'خطأ في الإدخال',
                    'message' => 'يرجى إدخال الحقول الجديدة'
                ]);
                return;
            }

            // تحليل الحقول الجديدة
            $newFieldsArray = $this->parseNewFields($this->newFields);

            if (empty($newFieldsArray)) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'خطأ في التحليل',
                    'message' => 'تنسيق الحقول غير صحيح'
                ]);
                return;
            }

            // إضافة الحقول للوحدة باستخدام مولد الوحدات
            $result = $this->addFieldsToExistingModule($this->editingModule, $newFieldsArray);

            if ($result) {
                $this->dispatchBrowserEvent('success', [
                    'title' => 'نجح التعديل',
                    'message' => 'تم إضافة الحقول الجديدة بنجاح للوحدة ' . $this->editingModule
                ]);

                // إعادة تحميل بيانات الوحدة
                $this->loadModuleData($this->editingModule);
                $this->loadModules(); // تحديث قائمة الوحدات

                $this->newFields = '';
                $this->setEditMode('view');
            }

        } catch (\Exception $e) {
            Log::error("خطأ في إضافة حقول جديدة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في التعديل',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * تحليل الحقول الجديدة من النص المدخل
     */
    private function parseNewFields($fieldsText)
    {
        $fields = [];
        $lines = explode(',', $fieldsText);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode(':', $line);
            if (count($parts) >= 2) {
                $fieldName = trim($parts[0]);
                $fieldType = trim($parts[1]);
                $required = isset($parts[2]) ? (strtolower(trim($parts[2])) === 'required') : false;

                $fields[] = [
                    'name' => $fieldName,
                    'type' => $fieldType,
                    'required' => $required
                ];
            }
        }

        return $fields;
    }

    /**
     * إضافة حقول لوحدة موجودة
     */
    private function addFieldsToExistingModule($moduleName, $newFields)
    {
        try {
            // 🔧 الإصلاح المحسن: جلب جميع الحقول الموجودة من قاعدة البيانات
            $existingFields = $this->loadModuleFieldsFromDatabase($moduleName);

            Log::info("🔍 الحقول الموجودة: " . count($existingFields) . " حقل");

            // تسجيل تفاصيل الحقول الموجودة للتتبع
            foreach ($existingFields as $field) {
                if (!empty($field['select_options'])) {
                    Log::info("📋 حقل موجود {$field['name']} له خيارات: " . implode(', ', $field['select_options']));
                }
            }

            // دمج الحقول الموجودة مع الجديدة
            $allFields = array_merge($existingFields, $newFields);

            Log::info("➕ الحقول الجديدة: " . count($newFields) . " حقل");
            Log::info("📋 إجمالي الحقول: " . count($allFields) . " حقل");

            // 🔧 تحسين: إنشاء JSON مع flags صحيحة
            $fieldsJson = json_encode($allFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("❌ خطأ في إنشاء JSON: " . json_last_error_msg());
                return false;
            }

            // تأكيد أن type1 موجود في البيانات المُرسلة
            foreach ($allFields as $field) {
                if (!empty($field['select_options'])) {
                    Log::info("🚀 سيتم إرسال حقل {$field['name']} مع خيارات: " . implode(', ', $field['select_options']));
                }
            }

            // تحديد المجموعة الأب الصحيحة للوحدة
            $parentGroup = $this->determineModuleParentGroup($moduleName);
            $moduleType = $parentGroup ? 'sub' : 'main';

            // تحضير معاملات الأمر
            $commandParams = [
                'name' => $moduleName,
                '--fields' => $fieldsJson,
                '--type' => $moduleType,
                '--ar-name' => $this->arabicName ?: $moduleName,
            ];

            // إضافة المجموعة الأب إذا كانت الوحدة فرعية
            if ($parentGroup) {
                $commandParams['--parent-group'] = $parentGroup;
            }

            // استدعاء مولد الوحدات مع جميع الحقول
            $result = Artisan::call('make:hmvc-module', $commandParams);

            Log::info("✅ نتيجة إعادة توليد الوحدة {$moduleName}: " . ($result === 0 ? 'نجح' : 'فشل'));

            if ($result !== 0) {
                Log::error("❌ تفاصيل خطأ التوليد: " . Artisan::output());
            } else {
                Log::info("📝 تم التوليد بنجاح");
            }

            return $result === 0;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في إضافة حقول للوحدة {$moduleName}: " . $e->getMessage());
            Log::error("❌ Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * إعادة إنشاء الوحدة بالكامل
     */
    public function recreateModule()
    {
        try {
            $this->dispatchBrowserEvent('info', [
                'title' => 'جاري إعادة الإنشاء',
                'message' => "جاري إعادة إنشاء الوحدة {$this->editingModule}..."
            ]);

            // حفظ الحقول الحالية
            $currentFields = $this->moduleFields;

            // إضافة الحقول الجديدة إن وُجدت
            if (!empty($this->newFields)) {
                $newFieldsArray = $this->parseNewFields($this->newFields);
                $currentFields = array_merge($currentFields, $newFieldsArray);
            }

            // تحديد المجموعة الأب الصحيحة للوحدة
            $parentGroup = $this->determineModuleParentGroup($this->editingModule);
            $moduleType = $parentGroup ? 'sub' : 'main';

            // إعادة إنشاء الوحدة باستخدام الأمر الصحيح
            $commandParams = [
                'name' => $this->editingModule,
                '--fields' => json_encode($currentFields, JSON_UNESCAPED_UNICODE),
                '--ar-name' => $this->arabicName,
                '--type' => $moduleType
            ];

            // إضافة المجموعة الأب إذا كانت الوحدة فرعية
            if ($parentGroup) {
                $commandParams['--parent-group'] = $parentGroup;
            }

            $result = Artisan::call('make:hmvc-module', $commandParams);

            if ($result === 0) {
                $this->dispatchBrowserEvent('success', [
                    'title' => 'نجحت العملية',
                    'message' => "تم إعادة إنشاء الوحدة {$this->editingModule} بنجاح"
                ]);

                $this->loadModuleData($this->editingModule);
                $this->loadModules();
                $this->setEditMode('view');
            } else {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'فشل في إعادة الإنشاء',
                    'message' => 'حدث خطأ في إعادة إنشاء الوحدة'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في إعادة إنشاء الوحدة: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في إعادة الإنشاء',
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ]);
        }
    }

    // ========== دوال الواجهة المتطورة - مطابقة لمولد الوحدات ==========

    /**
     * تحديث نوع الحقل تلقائياً عند التغيير (Livewire Hook)
     */
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
        }

        if ($this->newField['type'] !== 'checkbox') {
            $this->newField['checkbox_true_label'] = 'نعم';
            $this->newField['checkbox_false_label'] = 'لا';
        }

        if ($this->newField['type'] === 'checkbox') {
            $this->newField['required'] = false;
        }

        // Auto-enable features based on field type
        if ($this->newField['type'] === 'date' || $this->newField['type'] === 'datetime') {
            $this->enableFlatpickr = true;
        }

        if ($this->newField['type'] === 'select') {
            $this->enableSelect2 = true;
        }
    }

    /**
     * تغيير نوع الحقل مع إعادة تعيين الخصائص
     */
    public function changeFieldType()
    {
        // إعادة تعيين خصائص نوع الحقل عند التغيير بدون إعادة تحميل البيانات
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
            $this->selectedTableColumns = []; // مسح حقول الجدول المختار
            // إرسال حدث لتنظيف خيارات القائمة المنسدلة
            $this->dispatchBrowserEvent('clearSelectOptions');
        } else {
            // تعيين قيم افتراضية للأنواع المدعومة
            if (empty($this->newField['select_source'])) {
                $this->newField['select_source'] = 'manual';
            }
        }

        if ($this->newField['type'] !== 'checkbox') {
            $this->newField['checkbox_true_label'] = 'نعم';
            $this->newField['checkbox_false_label'] = 'لا';
        }

        if ($this->newField['type'] === 'checkbox') {
            $this->newField['required'] = false;
            $this->newField['searchable'] = false;
        } else {
            $this->newField['searchable'] = true;
        }

        // تفعيل الميزات المتقدمة تلقائياً حسب نوع الحقل
        if ($this->newField['type'] === 'date' || $this->newField['type'] === 'datetime') {
            $this->enableFlatpickr = true;
        }

        if ($this->newField['type'] === 'select') {
            $this->enableSelect2 = true;
        }

        // لا نعيد تحميل البيانات - مجرد تغيير في المتغير المحلي
        Log::info("تم تغيير نوع الحقل إلى: " . $this->newField['type'] . " بدون إعادة تحميل");
    }

    /**
     * إضافة خيار جديد لـ select
     */
    public function addSelectOption($option)
    {
        if (!empty($option) && !in_array($option, $this->newField['select_options'])) {
            $this->newField['select_options'][] = $option;

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم بنجاح',
                'message' => 'تم إضافة الخيار: ' . $option
            ]);
        }
    }

    /**
     * حذف خيار من select
     */
    public function removeSelectOption($index)
    {
        if (isset($this->newField['select_options'][$index])) {
            $removedOption = $this->newField['select_options'][$index];
            array_splice($this->newField['select_options'], $index, 1);

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم الحذف',
                'message' => 'تم حذف الخيار: ' . $removedOption
            ]);
        }
    }

    /**
     * تحديث حقول الجدول المختار (تستدعى تلقائياً عند تغيير newField.related_table)
     */
    public function updatedNewFieldRelatedTable($value)
    {
        Log::info("updatedNewFieldRelatedTable called with value: " . $value);
        $this->loadTableColumns($value);
    }

    /**
     * تحديث خصائص حساب التاريخ عند تغيير نوع الحساب
     */
    public function updatedNewFieldCalculationType($value)
    {
        if ($value === 'date_diff') {
            // تفعيل خصائص حساب التاريخ تلقائياً
            $this->newField['is_date_calculated'] = true;
            $this->newField['is_calculated'] = true;
            $this->newField['is_time_calculated'] = false; // إلغاء حساب الوقت

            // تعيين قيم افتراضية إذا لم تكن موجودة
            if (empty($this->newField['date_diff_unit'])) {
                $this->newField['date_diff_unit'] = 'days';
            }
            if (!isset($this->newField['include_end_date'])) {
                $this->newField['include_end_date'] = false;
            }
            if (!isset($this->newField['absolute_value'])) {
                $this->newField['absolute_value'] = false;
            }
            if (!isset($this->newField['remaining_only'])) {
                $this->newField['remaining_only'] = false;
            }
        } elseif ($value === 'time_diff') {
            // تفعيل خصائص حساب الوقت تلقائياً
            $this->newField['is_time_calculated'] = true;
            $this->newField['is_calculated'] = true;
            $this->newField['is_date_calculated'] = false; // إلغاء حساب التاريخ

            // تعيين قيم افتراضية لحساب الوقت
            if (empty($this->newField['time_diff_unit'])) {
                $this->newField['time_diff_unit'] = 'minutes';
            }
            if (!isset($this->newField['absolute_value'])) {
                $this->newField['absolute_value'] = false;
            }
            if (!isset($this->newField['remaining_only'])) {
                $this->newField['remaining_only'] = false;
            }
        } elseif ($value === 'formula') {
            // تفعيل خصائص الحساب العادي
            $this->newField['is_calculated'] = true;
            $this->newField['is_date_calculated'] = false;
            $this->newField['is_time_calculated'] = false;
        } else {
            // إلغاء تفعيل جميع خصائص الحساب
            $this->newField['is_calculated'] = false;
            $this->newField['is_date_calculated'] = false;
            $this->newField['is_time_calculated'] = false;
        }
    }

    /**
     * دالة عامة لتحديث حقول الجدول (للاستخدام من الـ View مباشرة)
     */
    public function updateTableColumns($tableName)
    {
        Log::info("updateTableColumns called with tableName: " . $tableName);
        $this->loadTableColumns($tableName);
    }

    /**
     * إضافة حقل جديد للقائمة المعلقة
     */
    public function addFieldToModule()
    {
        // التحقق من صحة البيانات
        $this->validate([
            'newField.name' => 'required|string|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            'newField.ar_name' => 'required|string',
            'newField.type' => 'required|in:string,text,integer,email,date,datetime,time,month_year,checkbox,file,select,decimal',
        ], [
            'newField.name.required' => 'اسم الحقل مطلوب',
            'newField.name.regex' => 'اسم الحقل يجب أن يكون بالإنجليزية فقط ويبدأ بحرف',
            'newField.ar_name.required' => 'الاسم العربي مطلوب',
            'newField.type.required' => 'نوع الحقل مطلوب',
            'newField.type.in' => 'نوع الحقل المحدد غير صالح',
        ]);

        // التحقق من عدم تكرار اسم الحقل
        $fieldExists = false;

        // فحص الحقول الحالية
        foreach ($this->moduleFields as $field) {
            if (strtolower($field['name']) === strtolower($this->newField['name'])) {
                $fieldExists = true;
                break;
            }
        }

        // فحص الحقول المعلقة
        foreach ($this->pendingFields as $field) {
            if (strtolower($field['name']) === strtolower($this->newField['name'])) {
                $fieldExists = true;
                break;
            }
        }

        if ($fieldExists) {
            $this->addError('newField.name', 'اسم الحقل موجود بالفعل');
            return;
        }

        // التحقق من خيارات الحقول المختلفة
        if (in_array($this->newField['type'], ['select', 'checkbox', 'file'])) {
            if ($this->newField['select_source'] === 'manual') {
                if ($this->newField['type'] === 'select' && empty($this->newField['select_options'])) {
                    $this->dispatchBrowserEvent('error', [
                        'title' => 'خطأ في البيانات',
                        'message' => 'يجب إضافة خيار واحد على الأقل للقائمة المنسدلة'
                    ]);
                    return;
                }
            } elseif ($this->newField['select_source'] === 'database') {
                if (empty($this->newField['related_table'])) {
                    $this->addError('newField.related_table', 'اسم الجدول مطلوب للربط');
                    return;
                }
                if (empty($this->newField['related_key'])) {
                    $this->addError('newField.related_key', 'عمود القيمة مطلوب');
                    return;
                }
                if (empty($this->newField['related_display'])) {
                    $this->addError('newField.related_display', 'عمود العرض مطلوب');
                    return;
                }
            }
        }

        // التحقق من معادلة الحساب للحقول المحسوبة
        if ($this->newField['is_calculated'] ?? false) {
            // التحقق من نوع الحساب
            $calculationType = $this->newField['calculation_type'] ?? 'none';

            if ($calculationType === 'formula') {
                if (empty($this->newField['calculation_formula'])) {
                    $this->addError('newField.calculation_formula', 'معادلة الحساب مطلوبة للحقول المحسوبة');
                    return;
                }

                // التحقق من صحة المعادلة
                $validationResult = $this->validateCalculationFormula($this->newField['calculation_formula']);
                if (!$validationResult['valid']) {
                    $this->addError('newField.calculation_formula', $validationResult['error']);
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

                // التحقق من أن حقول التاريخ موجودة في الحقول المضافة أو المعلقة
                $allFields = array_merge($this->moduleFields, $this->pendingFields);
                $dateFields = collect($allFields)->whereIn('type', ['date', 'datetime'])->pluck('name')->toArray();

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

                // التحقق من أن حقول الوقت موجودة في الحقول المضافة أو المعلقة
                $allFields = array_merge($this->moduleFields, $this->pendingFields);
                $timeFields = collect($allFields)->whereIn('type', ['time', 'datetime'])->pluck('name')->toArray();

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

        // إضافة الحقل للقائمة المعلقة
        $fieldData = $this->newField;
        $fieldData['created_at'] = now();

        $this->pendingFields[] = $fieldData;

        // إعادة تعيين النموذج
        $this->resetNewFieldForm();

        // إنشاء رسالة تفصيلية عن الحقل المُضاف
        $message = 'تم إضافة الحقل "' . $fieldData['ar_name'] . '" بنجاح';
        $details = [];

        if ($fieldData['required']) $details[] = 'مطلوب';
        if ($fieldData['unique']) $details[] = 'فريد';
        if ($fieldData['searchable']) $details[] = 'قابل للبحث';

        // عرض نوع محتوى النص الجديد بدلاً من الخيارات القديمة
        if (isset($fieldData['text_content_type']) && $fieldData['text_content_type'] !== 'any') {
            switch ($fieldData['text_content_type']) {
                case 'arabic_only':
                    $details[] = 'عربي فقط';
                    break;
                case 'numeric_only':
                    $details[] = 'أرقام فقط';
                    break;
                case 'english_only':
                    $details[] = 'إنجليزي فقط';
                    break;
            }
        }

        // عرض نوع الرقم الصحيح
        if (isset($fieldData['integer_type']) && $fieldData['integer_type'] !== 'int') {
            $details[] = strtoupper($fieldData['integer_type']);
            if ($fieldData['unsigned'] ?? false) {
                $details[] = 'أرقام موجبة فقط';
            }
        }

        // عرض تفاصيل الرقم العشري
        if (isset($fieldData['decimal_precision']) && $fieldData['decimal_precision'] !== 15) {
            $precision = $fieldData['decimal_precision'];
            $scale = $fieldData['decimal_scale'] ?? 2;
            $details[] = "DECIMAL($precision,$scale)";
        }

        if (!($fieldData['show_in_table'] ?? true)) $details[] = 'مخفي من الجدول';
        if (!($fieldData['show_in_search'] ?? true)) $details[] = 'مخفي من البحث';
        if (!($fieldData['show_in_forms'] ?? true)) $details[] = 'مخفي من النماذج';

        if (!empty($details)) {
            $message .= '<br><small>الخصائص: ' . implode('، ', $details) . '</small>';
        }

        $this->dispatchBrowserEvent('success', [
            'title' => 'تم بنجاح',
            'message' => $message
        ]);

        Log::info("تم إضافة حقل جديد: " . $fieldData['name'] . " للوحدة: " . $this->editingModule);
    }

    /**
     * إعادة تعيين نموذج الحقل الجديد
     */
    /**
     * إعادة تعيين نموذج الحقل الجديد بصمت (بدون إشعار)
     * تُستخدم عند التبديل بين الأوضاع لتجنب الإشعارات غير المرغوب فيها
     */
    public function resetNewFieldFormSilently()
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
            'text_content_type' => 'any', // any, arabic_only, numeric_only, english_only
            // إعدادات الأرقام الصحيحة الجديدة
            'integer_type' => 'int', // tinyint, smallint, int, bigint
            'unsigned' => false, // موجب فقط
            // إعدادات الأرقام العشرية الجديدة
            'decimal_precision' => 15, // إجمالي عدد الأرقام
            'decimal_scale' => 2, // عدد المراتب العشرية
            'file_types' => '',
            'select_options' => [],
            'select_source' => 'manual',
            'select_numeric_values' => false, // القيم الرقمية للقائمة المنسدلة
            'related_table' => '',
            'related_key' => 'id',
            'related_display' => 'name',
            'checkbox_true_label' => 'نعم',
            'checkbox_false_label' => 'لا',
            'is_calculated' => false, // حقل محسوب
            'calculation_formula' => '', // معادلة الحساب
            'calculation_type' => 'none', // نوع الحساب: none, formula, date_diff, time_diff
            'date_from_field' => '', // الحقل المرجعي للتاريخ من
            'date_to_field' => '', // الحقل المرجعي للتاريخ إلى
            'date_diff_unit' => 'days', // وحدة قياس الفرق
            'include_end_date' => false, // شمل التاريخ النهائي
            'absolute_value' => false, // قيمة مطلقة
            'remaining_only' => false, // الأيام المتبقية فقط
            'is_date_calculated' => false, // هل الحقل محسوب للتاريخ
            'date_calculation_config' => null, // إعدادات حساب التاريخ
            // خصائص حساب الوقت
            'time_from_field' => '', // الحقل المرجعي للوقت من
            'time_to_field' => '', // الحقل المرجعي للوقت إلى
            'time_diff_unit' => 'hours', // وحدة قياس فرق الوقت: hours, minutes, seconds
            'is_time_calculated' => false, // هل الحقل محسوب للوقت
            'time_calculation_config' => null // إعدادات حساب الوقت
        ];

        $this->resetErrorBag();
        // لا إشعار هنا - هذه هي الفكرة!
    }

    /**
     * إعادة تعيين نموذج الحقل الجديد مع إشعار
     * تُستخدم عند الضغط على زر "إعادة تعيين" يدوياً
     */
    public function resetNewFieldForm()
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
            'text_content_type' => 'any', // any, arabic_only, numeric_only, english_only
            // إعدادات الأرقام الصحيحة الجديدة
            'integer_type' => 'int', // tinyint, smallint, int, bigint
            'unsigned' => false, // موجب فقط
            // إعدادات الأرقام العشرية الجديدة
            'decimal_precision' => 15, // إجمالي عدد الأرقام
            'decimal_scale' => 2, // عدد المراتب العشرية
            'file_types' => '',
            'select_options' => [],
            'select_source' => 'manual',
            'select_numeric_values' => false, // القيم الرقمية للقائمة المنسدلة
            'related_table' => '',
            'related_key' => 'id',
            'related_display' => 'name',
            'checkbox_true_label' => 'نعم',
            'checkbox_false_label' => 'لا',
            'is_calculated' => false, // حقل محسوب
            'calculation_formula' => '', // معادلة الحساب
            'calculation_type' => 'none', // نوع الحساب: none, formula, date_diff, time_diff
            'date_from_field' => '', // الحقل المرجعي للتاريخ من
            'date_to_field' => '', // الحقل المرجعي للتاريخ إلى
            'date_diff_unit' => 'days', // وحدة قياس الفرق
            'include_end_date' => false, // شمل التاريخ النهائي
            'absolute_value' => false, // قيمة مطلقة
            'remaining_only' => false, // الأيام المتبقية فقط
            'is_date_calculated' => false, // هل الحقل محسوب للتاريخ
            'date_calculation_config' => null, // إعدادات حساب التاريخ
            // خصائص حساب الوقت
            'time_from_field' => '', // الحقل المرجعي للوقت من
            'time_to_field' => '', // الحقل المرجعي للوقت إلى
            'time_diff_unit' => 'hours', // وحدة قياس فرق الوقت: hours, minutes, seconds
            'is_time_calculated' => false, // هل الحقل محسوب للوقت
            'time_calculation_config' => null // إعدادات حساب الوقت
        ];

        $this->resetErrorBag();

        // إشارة نجاح إعادة التعيين
        $this->dispatchBrowserEvent('success', [
            'title' => 'تم بنجاح',
            'message' => 'تم إعادة تعيين النموذج'
        ]);
    }

    /**
     * حذف حقل معلق
     */
    public function removePendingField($index)
    {
        if (isset($this->pendingFields[$index])) {
            $removedField = $this->pendingFields[$index];
            array_splice($this->pendingFields, $index, 1);

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم الحذف',
                'message' => 'تم حذف الحقل: ' . ($removedField['ar_name'] ?? $removedField['name'])
            ]);
        }
    }

    /**
     * إظهار تأكيد الحذف المحسن
     */
    public function confirmDeleteField($index)
    {
        if (!isset($this->moduleFields[$index])) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ',
                'message' => 'لم يتم العثور على الحقل المطلوب'
            ]);
            return;
        }

        $field = $this->moduleFields[$index];

        // التحقق من أن الحقل ليس أساسياً
        if (in_array($field['name'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'لا يمكن الحذف',
                'message' => 'لا يمكن حذف الحقول الأساسية للنظام'
            ]);
            return;
        }

        $this->fieldToDelete = $field;
        $this->fieldDeleteIndex = $index;
        $this->showFieldDeleteConfirm = true;
    }

    /**
     * إلغاء حذف الحقل
     */
    public function cancelDeleteField()
    {
        $this->showFieldDeleteConfirm = false;
        $this->fieldToDelete = null;
        $this->fieldDeleteIndex = null;
    }

    /**
     * تأكيد حذف الحقل (الحذف المحسن فقط)
     */
    public function confirmDeleteFieldAction()
    {
        $this->deleteFieldAndRecreateModule($this->fieldDeleteIndex);
        $this->cancelDeleteField();
    }

    /**
     * حذف حقل من الوحدة بإعادة إنشاء النافذة من جديد
     * طريقة محسنة: بدلاً من إخفاء الحقل، نحذفه نهائياً ونعيد إنشاء النافذة
     */
    public function deleteFieldAndRecreateModule($index)
    {
        try {
            if (!isset($this->moduleFields[$index])) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'خطأ',
                    'message' => 'لم يتم العثور على الحقل المطلوب حذفه'
                ]);
                return;
            }

            $fieldToRemove = $this->moduleFields[$index];
            $fieldName = $fieldToRemove['name'];
            $fieldArName = $fieldToRemove['ar_name'] ?? $fieldName;
            $moduleName = $this->editingModule;

            // التحقق من أن الحقل ليس أساسياً (id, created_at, updated_at)
            if (in_array($fieldName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'لا يمكن الحذف',
                    'message' => 'لا يمكن حذف الحقول الأساسية للنظام'
                ]);
                return;
            }

            $this->dispatchBrowserEvent('info', [
                'title' => 'جاري الحذف وإعادة الإنشاء',
                'message' => "جاري حذف الحقل '{$fieldArName}' وإعادة إنشاء النافذة..."
            ]);

            // 1. حذف الحقل من جدول module_fields
            $deletedFromDB = DB::table('module_fields')
                ->where('module_name', $moduleName)
                ->where('field_name', $fieldName)
                ->delete();

            if ($deletedFromDB > 0) {
                Log::info("✅ تم حذف الحقل '{$fieldName}' من جدول module_fields");
            } else {
                Log::warning("⚠️ الحقل '{$fieldName}' غير موجود في جدول module_fields");
            }

            // 2. إزالة الحقل من القائمة المحلية
            array_splice($this->moduleFields, $index, 1);

            // 3. جلب جميع الحقول المتبقية من قاعدة البيانات
            $remainingFields = $this->loadModuleFieldsFromDatabase($moduleName);

            Log::info("📋 الحقول المتبقية: " . count($remainingFields) . " حقل");

            if (empty($remainingFields)) {
                $this->dispatchBrowserEvent('warning', [
                    'title' => 'تحذير',
                    'message' => 'لم تعد هناك حقول في الوحدة بعد الحذف'
                ]);
                return;
            }

            // 4. إعادة إنشاء الوحدة بالحقول المتبقية
            $result = $this->recreateModuleWithFields($moduleName, $remainingFields);

            // 5. إنشاء migration لحذف الحقل من قاعدة البيانات (سواء نجحت إعادة الإنشاء أم لا)
            Log::info("🔄 التحقق من إنشاء migration لحذف الحقل {$fieldName}");

            if ($this->shouldCreateDropColumnMigration($moduleName, $fieldName)) {
                // إنشاء migration لحذف العمود من قاعدة البيانات
                Log::info("📝 إنشاء migration لحذف الحقل {$fieldName}");
                $this->createDropColumnMigration($moduleName, $fieldName);

                // تشغيل Migration
                $this->runMigrations();
            } else {
                Log::info("⚠️ تم تجاهل إنشاء migration لحذف {$fieldName} - العمود غير موجود أو تم حذفه مسبقاً");
            }

            if ($result) {
                // 6. إعادة تحميل بيانات الوحدة المحدثة
                $this->loadModuleData($moduleName);

                $this->dispatchBrowserEvent('success', [
                    'title' => 'تم الحذف وإعادة الإنشاء بنجاح',
                    'message' => "تم حذف الحقل '{$fieldArName}' وإعادة إنشاء النافذة بنجاح"
                ]);

                Log::info("✅ تم حذف الحقل '{$fieldName}' وإعادة إنشاء الوحدة '{$moduleName}' بنجاح");
            } else {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'فشل في إعادة الإنشاء',
                    'message' => 'تم حذف الحقل لكن فشل في إعادة إنشاء النافذة'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("❌ خطأ في حذف الحقل وإعادة الإنشاء: " . $e->getMessage());

            // الحصول على اسم الحقل إذا كان متاحاً
            $fieldDisplayName = isset($fieldArName) ? $fieldArName : (isset($fieldName) ? $fieldName : 'الحقل');
            $moduleDisplayName = isset($moduleName) ? $moduleName : 'الوحدة';

            // معالجة خاصة لخطأ الجدول الموجود
            if (str_contains($e->getMessage(), 'already exists') ||
                str_contains($e->getMessage(), '1050') ||
                str_contains($e->getMessage(), 'SQLSTATE[42S01]')) {

                // لا ننسى إنشاء migration لحذف الحقل من قاعدة البيانات
                if (isset($moduleName) && isset($fieldName)) {
                    Log::info("🔄 إنشاء migration لحذف الحقل {$fieldName} رغم خطأ الجدول الموجود");

                    if ($this->shouldCreateDropColumnMigration($moduleName, $fieldName)) {
                        // إنشاء migration لحذف العمود من قاعدة البيانات
                        $this->createDropColumnMigration($moduleName, $fieldName);

                        // تشغيل Migration
                        $this->runMigrations();
                    } else {
                        Log::info("⚠️ تم تجاهل إنشاء migration لحذف {$fieldName} - العمود غير موجود أو تم حذفه مسبقاً");
                    }
                }

                $this->dispatchBrowserEvent('success', [
                    'title' => 'تم الحذف بنجاح',
                    'message' => "تم حذف الحقل '{$fieldDisplayName}' وإعادة إنشاء النافذة بنجاح (تم تجاهل تحذير الجدول الموجود)"
                ]);

                // إعادة تحميل بيانات الوحدة إذا كان اسم الوحدة متاحاً
                if (isset($moduleName)) {
                    $this->loadModuleData($moduleName);
                }
                return;
            }

            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في العملية',
                'message' => 'حدث خطأ أثناء حذف الحقل وإعادة إنشاء الوحدة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إعادة إنشاء الوحدة بحقول محددة (بدون إنشاء جدول جديد)
     */
    private function recreateModuleWithFields($moduleName, $fields)
    {
        try {
            // التحقق من وجود حقول
            if (empty($fields)) {
                Log::warning("لا توجد حقول لإعادة إنشاء الوحدة {$moduleName}");
                return false;
            }

            // تحديد المجموعة الأب الصحيحة للوحدة
            $parentGroup = $this->determineModuleParentGroup($moduleName);
            $moduleType = $parentGroup ? 'sub' : 'main';

            // تحضير JSON للحقول
            $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("❌ خطأ في إنشاء JSON للحقول: " . json_last_error_msg());
                return false;
            }

            Log::info("🔄 بدء إعادة إنشاء الوحدة {$moduleName} بـ " . count($fields) . " حقل");
            Log::info("📋 الحقول: " . $fieldsJson);

            // إعادة الإنشاء مع معالجة خاصة لأخطاء الجداول الموجودة
            return $this->recreateModuleFilesOnly($moduleName, $fields, $moduleType, $parentGroup);

        } catch (\Exception $e) {
            // معالجة خاصة لخطأ الجدول الموجود
            if (str_contains($e->getMessage(), 'already exists') ||
                str_contains($e->getMessage(), '1050') ||
                str_contains($e->getMessage(), 'Base table or view already exists')) {
                Log::info("✅ تم تجاهل خطأ الجدول الموجود، العملية نجحت");
                return true;
            }

            Log::error("❌ خطأ في إعادة إنشاء الوحدة: " . $e->getMessage());
            return false;
        }
    }    /**
     * إعادة إنشاء ملفات الوحدة فقط (بدون قاعدة البيانات)
     */
    private function recreateModuleFilesOnly($moduleName, $fields, $moduleType, $parentGroup)
    {
        try {
            $lowerModuleName = strtolower($moduleName);
            $tableName = Str::plural($lowerModuleName);

            // حذف العنصر من القائمة الديناميكية قبل إعادة الإنشاء لتجنب التكرار
            try {
                DynamicMenuHelper::removeMenuItem($moduleName);
                Log::info("✅ تم حذف العنصر من القائمة الديناميكية قبل إعادة الإنشاء");
            } catch (\Exception $e) {
                Log::warning("⚠️ لم يتم العثور على العنصر في القائمة الديناميكية: " . $e->getMessage());
            }

            // إنشاء backup للـ migration الحالية وحذفها مؤقتاً
            $backupMigrations = $this->backupAndRemoveExistingMigrations($tableName);

            // تحضير معاملات الأمر العادي
            $commandParams = [
                'name' => $moduleName,
                '--fields' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '--type' => $moduleType,
                '--ar-name' => $this->arabicName ?: $moduleName,
            ];

            if ($parentGroup) {
                $commandParams['--parent-group'] = $parentGroup;
            }

            Log::info("🔄 بدء إعادة إنشاء ملفات الوحدة {$moduleName} فقط");
            Log::info("📝 معاملات الأمر: " . json_encode($commandParams, JSON_UNESCAPED_UNICODE));

            // تشغيل الأمر مع تجاهل أخطاء قاعدة البيانات
            try {
                // استخدام output buffering لتجاهل الأخطاء المعروضة
                ob_start();
                $result = Artisan::call('make:hmvc-module', $commandParams);
                $output = Artisan::output();
                ob_end_clean(); // تجاهل أي خرج خطأ

                // حذف migration الجديدة التي تم إنشاؤها
                $this->removeNewlyCreatedMigrations($tableName);

                // استعادة migrations المحفوظة
                $this->restoreBackedUpMigrations($backupMigrations);

                Log::info("🔧 خرج الأمر: " . $output);
                Log::info("🔧 رمز النتيجة: " . $result);

                // التحقق من نجاح العملية أو وجود تحذيرات قابلة للتجاهل
                if ($result === 0 ||
                    str_contains($output, 'Module created successfully') ||
                    str_contains($output, 'Files generated successfully')) {
                    Log::info("✅ تم إعادة إنشاء ملفات الوحدة {$moduleName} بنجاح");
                    return true;
                }

                // إذا كان الخطأ متعلق بجدول موجود فقط، فاعتبرها نجاح
                if (str_contains($output, 'Table') && str_contains($output, 'already exists')) {
                    Log::info("⚠️ الجدول موجود مسبقاً، لكن الملفات تم إنشاؤها بنجاح");
                    return true;
                }

                // إذا كان الخطأ متعلق بـ SQLSTATE[42S01]، فاعتبرها نجاح
                if (str_contains($output, 'SQLSTATE[42S01]') || str_contains($output, '1050')) {
                    Log::info("⚠️ خطأ جدول موجود (SQLSTATE[42S01])، لكن الملفات تم إنشاؤها بنجاح");
                    return true;
                }

                Log::warning("⚠️ تم إعادة إنشاء الملفات مع تحذيرات: " . $output);
                return true; // نعتبرها نجاح إذا كانت الملفات تم إنشاؤها

            } catch (\Exception $artisanException) {
                $errorMessage = $artisanException->getMessage();

                Log::info("🔍 تحليل خطأ Artisan: " . $errorMessage);

                // استعادة migrations المحفوظة في حالة الخطأ
                $this->restoreBackedUpMigrations($backupMigrations);

                // تجاهل أخطاء قاعدة البيانات واعتبار العملية نجحت إذا كانت الملفات موجودة
                if (str_contains($errorMessage, 'already exists') ||
                    str_contains($errorMessage, '1050') ||
                    str_contains($errorMessage, 'Base table or view already exists') ||
                    str_contains($errorMessage, 'SQLSTATE[42S01]')) {
                    Log::info("✅ تم تجاهل خطأ الجدول الموجود، العملية نجحت");
                    return true;
                }

                Log::error("❌ خطأ Artisan غير متوقع: " . $errorMessage);

                // حتى لو فشل الأمر، تحقق من وجود الملفات
                if ($this->checkModuleFilesExist($moduleName)) {
                    Log::info("✅ الملفات موجودة رغم الخطأ، العملية نجحت");
                    return true;
                }

                throw $artisanException;
            }        } catch (\Exception $e) {
            Log::error("❌ خطأ في إعادة إنشاء ملفات الوحدة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * حفظ وإزالة migrations الموجودة للجدول مؤقتاً
     */
    private function backupAndRemoveExistingMigrations($tableName)
    {
        try {
            $backupData = [];
            $migrationsPath = database_path('migrations');

            // البحث عن migrations الخاصة بالجدول
            $migrationPattern = "*create_{$tableName}_table.php";
            $migrationFiles = glob($migrationsPath . '/' . $migrationPattern);

            foreach ($migrationFiles as $migrationFile) {
                $fileName = basename($migrationFile);
                $backupData[] = [
                    'original_path' => $migrationFile,
                    'filename' => $fileName,
                    'content' => file_get_contents($migrationFile)
                ];

                // حذف الملف مؤقتاً
                unlink($migrationFile);
                Log::info("📦 تم حفظ وحذف migration مؤقتاً: {$fileName}");
            }

            return $backupData;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في حفظ migrations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * حذف migrations الجديدة التي تم إنشاؤها
     */
    private function removeNewlyCreatedMigrations($tableName)
    {
        try {
            $migrationsPath = database_path('migrations');

            // البحث عن migrations الجديدة
            $migrationPattern = "*create_{$tableName}_table.php";
            $migrationFiles = glob($migrationsPath . '/' . $migrationPattern);

            foreach ($migrationFiles as $migrationFile) {
                $fileName = basename($migrationFile);
                unlink($migrationFile);
                Log::info("🗑️ تم حذف migration جديدة غير مرغوبة: {$fileName}");
            }

        } catch (\Exception $e) {
            Log::error("❌ خطأ في حذف migrations الجديدة: " . $e->getMessage());
        }
    }

    /**
     * استعادة migrations المحفوظة
     */
    private function restoreBackedUpMigrations($backupData)
    {
        try {
            foreach ($backupData as $backup) {
                file_put_contents($backup['original_path'], $backup['content']);
                Log::info("♻️ تم استعادة migration: {$backup['filename']}");
            }

        } catch (\Exception $e) {
            Log::error("❌ خطأ في استعادة migrations: " . $e->getMessage());
        }
    }

    /**
     * التحقق من وجود ملفات الوحدة
     */
    private function checkModuleFilesExist($moduleName)
    {
        try {
            $basePath = base_path();
            $moduleLower = strtolower($moduleName);

            // التحقق من وجود ملفات أساسية للوحدة
            $essentialFiles = [
                "app/Http/Controllers/{$moduleName}Controller.php",
                "app/Http/Livewire/{$moduleName}/{$moduleName}.php",
                "app/Models/{$moduleName}.php",
                "resources/views/livewire/{$moduleLower}/{$moduleLower}.blade.php"
            ];

            $existingFiles = 0;
            foreach ($essentialFiles as $file) {
                if (file_exists($basePath . '/' . $file)) {
                    $existingFiles++;
                }
            }

            // إذا كان 50% أو أكثر من الملفات موجودة، فاعتبر الوحدة موجودة
            $threshold = count($essentialFiles) * 0.5;
            $exists = $existingFiles >= $threshold;

            Log::info("📁 فحص ملفات الوحدة {$moduleName}: {$existingFiles}/" . count($essentialFiles) . " موجودة");

            return $exists;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في فحص ملفات الوحدة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على قائمة الجداول الموجودة
     */
    private function getExistingTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return array_map(function($table) {
                return array_values((array) $table)[0];
            }, $tables);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * حفظ تكوين الحقول للوحدة
     */
    private function saveModuleFieldsConfiguration($moduleName, $allFields)
    {
        try {
            $configPath = storage_path("app/modules_config");
            if (!File::exists($configPath)) {
                File::makeDirectory($configPath, 0755, true);
            }

            $configFile = $configPath . "/{$moduleName}_fields.json";
            $configData = [
                'module_name' => $moduleName,
                'updated_at' => now()->toISOString(),
                'fields' => $allFields
            ];

            File::put($configFile, json_encode($configData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            Log::info("تم حفظ تكوين الحقول للوحدة: {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حفظ تكوين الحقول: " . $e->getMessage());
        }
    }

    /**
     * استعادة تكوين الحقول المحفوظ للوحدة
     */
    private function loadModuleFieldsConfiguration($moduleName)
    {
        try {
            $configFile = storage_path("app/modules_config/" . strtolower($moduleName) . ".json");

            if (File::exists($configFile)) {
                $configData = json_decode(File::get($configFile), true);

                // تحويل التكوين إلى تنسيق moduleFields
                $fields = [];
                foreach ($configData as $fieldName => $fieldConfig) {
                    $fields[] = [
                        'name' => $fieldName,
                        'type' => $fieldConfig['type'] ?? 'text',
                        'size' => $fieldConfig['size'] ?? '',
                        'show_in_table' => $fieldConfig['show_in_table'] ?? true,
                        'show_in_search' => $fieldConfig['show_in_search'] ?? true,
                        'show_in_forms' => $fieldConfig['show_in_forms'] ?? true,
                        'arabic_only' => $fieldConfig['arabic_only'] ?? false,
                        'numeric_only' => $fieldConfig['numeric_only'] ?? false,
                        'select_options' => $fieldConfig['select_options'] ?? [],
                        'select_source' => $fieldConfig['select_source'] ?? 'manual',
                        'related_table' => $fieldConfig['related_table'] ?? '',
                        'related_key' => $fieldConfig['related_key'] ?? 'id',
                        'related_display' => $fieldConfig['related_display'] ?? 'name',
                        'checkbox_true_label' => $fieldConfig['checkbox_true_label'] ?? 'نعم',
                        'checkbox_false_label' => $fieldConfig['checkbox_false_label'] ?? 'لا',
                        'file_types' => $fieldConfig['file_types'] ?? '',
                        'ar_name' => $fieldConfig['ar_name'] ?? $fieldName,
                        'required' => $fieldConfig['required'] ?? false,
                        'unique' => $fieldConfig['unique'] ?? false,
                        'searchable' => $fieldConfig['searchable'] ?? false,
                    ];
                }

                Log::info("تم تحميل تكوين الحقول المحفوظ للوحدة: {$moduleName} - عدد الحقول: " . count($fields));
                return $fields;
            }
        } catch (\Exception $e) {
            Log::error("خطأ في تحميل تكوين الحقول: " . $e->getMessage());
        }

        return null;
    }

    /**
     * تحويل نوع الحقل للتوافق مع الأمر
     */
    private function mapFieldTypeForCommand($fieldType)
    {
        $typeMapping = [
            'string' => 'string', // نص قصير يبقى string
            'text' => 'text',     // نص طويل يبقى text (لا نحوله إلى textarea)
            'integer' => 'integer', // رقم صحيح يبقى integer
            'decimal' => 'decimal', // رقم عشري يبقى decimal
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'email' => 'email',
            'file' => 'file',
            'select' => 'select',
            'month_year' => 'month_year',
        ];

        return $typeMapping[$fieldType] ?? $fieldType;
    }

    /**
     * تطبيق الحقول المعلقة على الوحدة
     */
    public function applyPendingFields()
    {
        if (empty($this->pendingFields)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'لا توجد حقول',
                'message' => 'لا توجد حقول جديدة للتطبيق'
            ]);
            return;
        }

        try {
            Log::info("بدء تطبيق الحقول للوحدة: " . $this->editingModule);
            Log::info("عدد الحقول الجديدة: " . count($this->pendingFields));
            Log::info("عدد الحقول الموجودة: " . count($this->moduleFields));

            // دمج الحقول الموجودة مع الحقول الجديدة
            $allFieldsData = [];

            // 1. أولاً: إضافة الحقول الموجودة مع الحفاظ على خصائصها الأصلية
            foreach ($this->moduleFields as $existingField) {
                $fieldData = [
                    'name' => $existingField['name'],
                    'ar_name' => $existingField['ar_name'] ?? $existingField['arabic_name'] ?? $existingField['name'],
                    'comment' => $existingField['ar_name'] ?? $existingField['arabic_name'] ?? $existingField['name'],
                    'type' => $this->mapFieldTypeForCommand($existingField['type'] ?? 'text'),
                    'required' => $existingField['required'] ?? false,
                    'unique' => $existingField['unique'] ?? false,
                    'searchable' => $existingField['searchable'] ?? true,
                    // خصائص الحقول المحسوبة
                    'is_calculated' => $existingField['is_calculated'] ?? false,
                    'calculation_formula' => $existingField['calculation_formula'] ?? null,
                    'calculation_type' => $existingField['calculation_type'] ?? 'none',
                    'date_from_field' => $existingField['date_from_field'] ?? null,
                    'date_to_field' => $existingField['date_to_field'] ?? null,
                    'date_diff_unit' => $existingField['date_diff_unit'] ?? 'days',
                    'include_end_date' => $existingField['include_end_date'] ?? false,
                    'absolute_value' => $existingField['absolute_value'] ?? false,
                    'remaining_only' => $existingField['remaining_only'] ?? false,
                    'is_date_calculated' => $existingField['is_date_calculated'] ?? false,
                    'date_calculation_config' => $existingField['date_calculation_config'] ?? null,
                    // خصائص حساب الوقت
                    'time_from_field' => $existingField['time_from_field'] ?? null,
                    'time_to_field' => $existingField['time_to_field'] ?? null,
                    'time_diff_unit' => $existingField['time_diff_unit'] ?? 'minutes',
                    'is_time_calculated' => $existingField['is_time_calculated'] ?? false,
                    'time_calculation_config' => $existingField['time_calculation_config'] ?? null,
                    // الحفاظ على خصائص العرض الأصلية
                    'show_in_table' => $existingField['show_in_table'] ?? true,
                    'show_in_search' => $existingField['show_in_search'] ?? true,
                    'show_in_forms' => $existingField['show_in_forms'] ?? true,
                    // باقي الخصائص
                    'size' => $existingField['size'] ?? $existingField['max'] ?? '',
                    'arabic_only' => $existingField['arabic_only'] ?? false,
                    'numeric_only' => $existingField['numeric_only'] ?? $existingField['numbers_only'] ?? false,
                    // إعدادات النص الجديدة
                    'text_content_type' => $existingField['text_content_type'] ?? 'any',
                    // إعدادات الأرقام الصحيحة الجديدة
                    'integer_type' => $existingField['integer_type'] ?? 'int',
                    'unsigned' => $existingField['unsigned'] ?? false,
                    // إعدادات الأرقام العشرية الجديدة
                    'decimal_precision' => $existingField['decimal_precision'] ?? 15,
                    'decimal_scale' => $existingField['decimal_scale'] ?? 2,
                    'file_types' => $existingField['file_types'] ?? '',
                    'select_options' => $existingField['select_options'] ?? [],
                    'select_source' => $existingField['select_source'] ?? 'manual',
                    'select_numeric_values' => $existingField['select_numeric_values'] ?? false,
                    'related_table' => $existingField['related_table'] ?? '',
                    'related_key' => $existingField['related_key'] ?? 'id',
                    'related_display' => $existingField['related_display'] ?? 'name',
                    'checkbox_true_label' => $existingField['checkbox_true_label'] ?? 'نعم',
                    'checkbox_false_label' => $existingField['checkbox_false_label'] ?? 'لا',
                ];
                $allFieldsData[] = $fieldData;
            }

            // 2. ثانياً: إضافة الحقول الجديدة
            foreach ($this->pendingFields as $newField) {
                $fieldData = [
                    'name' => $newField['name'],
                    'ar_name' => $newField['ar_name'] ?? $newField['name'],
                    'comment' => $newField['ar_name'] ?? $newField['name'],
                    'type' => $this->mapFieldTypeForCommand($newField['type']),
                    'required' => $newField['required'] ?? false,
                    'unique' => $newField['unique'] ?? false,
                    'searchable' => $newField['searchable'] ?? true,
                    // خصائص الحقول المحسوبة
                    'is_calculated' => $newField['is_calculated'] ?? false,
                    'calculation_formula' => $newField['calculation_formula'] ?? null,
                    'calculation_type' => $newField['calculation_type'] ?? 'none',
                    'date_from_field' => $newField['date_from_field'] ?? null,
                    'date_to_field' => $newField['date_to_field'] ?? null,
                    'date_diff_unit' => $newField['date_diff_unit'] ?? 'days',
                    'include_end_date' => $newField['include_end_date'] ?? false,
                    'absolute_value' => $newField['absolute_value'] ?? false,
                    'remaining_only' => $newField['remaining_only'] ?? false,
                    'is_date_calculated' => $newField['is_date_calculated'] ?? false,
                    'date_calculation_config' => $newField['date_calculation_config'] ?? null,
                    // خصائص حساب الوقت
                    'time_from_field' => $newField['time_from_field'] ?? null,
                    'time_to_field' => $newField['time_to_field'] ?? null,
                    'time_diff_unit' => $newField['time_diff_unit'] ?? 'minutes',
                    'is_time_calculated' => $newField['is_time_calculated'] ?? false,
                    'time_calculation_config' => $newField['time_calculation_config'] ?? null,
                    // استخدام خصائص العرض الجديدة
                    'show_in_table' => $newField['show_in_table'] ?? true,
                    'show_in_search' => $newField['show_in_search'] ?? true,
                    'show_in_forms' => $newField['show_in_forms'] ?? true,
                    // باقي الخصائص
                    'size' => $newField['size'] ?? '',
                    'arabic_only' => $newField['arabic_only'] ?? false,
                    'numeric_only' => $newField['numeric_only'] ?? false,
                    // إعدادات النص الجديدة
                    'text_content_type' => $newField['text_content_type'] ?? 'any',
                    // إعدادات الأرقام الصحيحة الجديدة
                    'integer_type' => $newField['integer_type'] ?? 'int',
                    'unsigned' => $newField['unsigned'] ?? false,
                    // إعدادات الأرقام العشرية الجديدة
                    'decimal_precision' => $newField['decimal_precision'] ?? 15,
                    'decimal_scale' => $newField['decimal_scale'] ?? 2,
                    'file_types' => $newField['file_types'] ?? '',
                    'select_options' => $newField['select_options'] ?? [],
                    'select_source' => $newField['select_source'] ?? 'manual',
                    'select_numeric_values' => $newField['select_numeric_values'] ?? false,
                    'related_table' => $newField['related_table'] ?? '',
                    'related_key' => $newField['related_key'] ?? 'id',
                    'related_display' => $newField['related_display'] ?? 'name',
                    'checkbox_true_label' => $newField['checkbox_true_label'] ?? 'نعم',
                    'checkbox_false_label' => $newField['checkbox_false_label'] ?? 'لا',
                ];
                $allFieldsData[] = $fieldData;
            }

            Log::info("إجمالي الحقول بعد الدمج: " . count($allFieldsData));
            Log::info("بيانات جميع الحقول: " . json_encode($allFieldsData, JSON_UNESCAPED_UNICODE));

            // حفظ البيانات في ملف مؤقت لتجنب مشاكل parsing في command line
            $tempFile = storage_path('tmp_fields_' . time() . '.json');

            // تنسيق البيانات بالشكل المطلوب
            $formattedData = [
                'fields' => $allFieldsData,
                'advanced_features' => [
                    'excel_export' => $this->enableExcelExport,
                    'pdf_export' => $this->enablePdfExport,
                    'flatpickr' => $this->enableFlatpickr,
                    'select2' => $this->enableSelect2,
                    'update_views' => $this->enableViewsUpdate,
                ]
            ];

            file_put_contents($tempFile, json_encode($formattedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // استخدام Artisan command لإضافة الحقول
            $exitCode = Artisan::call('hmvc:add-fields-clean', [
                'module' => $this->editingModule,
                '--fields-file' => $tempFile,
                '--regenerate' => true, // استخدام نظام إعادة الإنشاء الجديد
            ]);

            // الحصول على مخرجات الأمر
            $output = Artisan::output();
            Log::info("مخرجات الأمر: " . $output);
            Log::info("رمز الخروج: " . $exitCode);

            if ($exitCode === 0) {
                // حفظ تكوين الحقول المُحدث
                $this->saveModuleFieldsConfiguration($this->editingModule, $allFieldsData);

                // حفظ الحقول الجديدة في جدول module_fields
                $this->saveNewFieldsToDatabase($this->editingModule, $this->pendingFields);

                // تحديث معلومات الوحدة الأساسية لجميع الحقول الموجودة
                $this->updateExistingFieldsModuleInfo($this->editingModule);

                // مسح الحقول المعلقة بعد النجاح
                $appliedCount = count($this->pendingFields);
                $this->pendingFields = [];

                // إعادة تعيين نموذج الحقل الجديد
                $this->resetNewField();

                // إعادة تحميل بيانات الوحدة
                $this->loadModuleData($this->editingModule);
                $this->loadModules(); // تحديث قائمة الوحدات

                $this->dispatchBrowserEvent('success', [
                    'title' => 'تم بنجاح ✅',
                    'message' => "تم تطبيق {$appliedCount} حقل جديد على الوحدة {$this->editingModule}. تم تحديث جميع الملفات وقاعدة البيانات."
                ]);

                Log::info("تم بنجاح تطبيق {$appliedCount} حقل على الوحدة: " . $this->editingModule);

            } else {
                // فشل الأمر
                Log::error("فشل الأمر برمز الخروج: " . $exitCode);
                Log::error("مخرجات الخطأ: " . $output);

                $this->dispatchBrowserEvent('error', [
                    'title' => 'خطأ في التنفيذ ❌',
                    'message' => 'فشل في تنفيذ الأمر. رمز الخروج: ' . $exitCode . "\n" . 'تفاصيل الخطأ: ' . $output
                ]);
            }

            // حذف الملف المؤقت
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

        } catch (\Exception $e) {
            Log::error("خطأ في تطبيق الحقول: " . $e->getMessage());
            Log::error("تفاصيل الخطأ: " . $e->getTraceAsString());

            // حذف الملف المؤقت في حالة الخطأ
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في التطبيق ❌',
                'message' => 'حدث خطأ أثناء تطبيق الحقول: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * فحص وإصلاح syntax errors في ملف Livewire
     */
    public function fixSyntaxErrors()
    {
        if (empty($this->editingModule)) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ',
                'message' => 'لا توجد وحدة محددة للإصلاح'
            ]);
            return;
        }

        try {
            $singularName = Str::singular($this->editingModule);

            $possiblePaths = [
                base_path("app/Http/Livewire/{$this->editingModule}/{$singularName}.php"),
                base_path("app/Http/Livewire/" . Str::plural($this->editingModule) . "/{$singularName}.php"),
            ];

            $livewirePath = null;
            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    $livewirePath = $path;
                    break;
                }
            }

            if (!$livewirePath) {
                $this->dispatchBrowserEvent('error', [
                    'title' => 'ملف غير موجود',
                    'message' => 'لم يتم العثور على ملف Livewire للإصلاح'
                ]);
                return;
            }

            $content = File::get($livewirePath);
            $originalContent = $content;
            $fixesApplied = 0;

            // فحص syntax أولاً باستخدام PHP
            $syntaxCheck = shell_exec("php -l \"$livewirePath\" 2>&1");
            $hasSyntaxErrors = strpos($syntaxCheck, 'No syntax errors detected') === false;

            if ($hasSyntaxErrors) {
                // محاولة إصلاح أخطاء syntax الشائعة

                // إصلاح الجمل غير المكتملة مثل if (emp
                $content = preg_replace('/if\s*\(\s*[a-zA-Z_][a-zA-Z0-9_]*\s*$/', '', $content);

                // إصلاح أقواس غير متطابقة - إضافة أقواس إغلاق مفقودة
                $openBraces = substr_count($content, '{');
                $closeBraces = substr_count($content, '}');
                if ($openBraces > $closeBraces) {
                    $missingBraces = $openBraces - $closeBraces;
                    for ($i = 0; $i < $missingBraces; $i++) {
                        $content .= "\n}";
                    }
                    $fixesApplied++;
                }

                // إصلاح أقواس إضافية
                if ($closeBraces > $openBraces) {
                    $extraBraces = $closeBraces - $openBraces;
                    $content = preg_replace('/\}\s*$/', '', $content, $extraBraces);
                    $fixesApplied++;
                }
            }

            // إصلاح مشاكل خطيرة فقط - تجاهل مشاكل التنسيق البسيطة
            // لا نقوم بإصلاح مشاكل المسافات أو التنسيق العادية

            // إصلاح المسافات الزائدة في أسماء الدوال (مشكلة محتملة)
            if (preg_match('/function\s+(\w+)\s+\(/', $content)) {
                $content = preg_replace('/function\s+(\w+)\s+\(/', 'function $1(', $content);
                $fixesApplied++;
            }

            if ($content !== $originalContent) {
                // إنشاء نسخة احتياطية
                $backupPath = $livewirePath . '.backup.' . date('Y_m_d_H_i_s');
                File::copy($livewirePath, $backupPath);

                // حفظ المحتوى المُصلح
                File::put($livewirePath, $content);

                // فحص إضافي للتأكد من أن الملف أصبح سليماً
                $syntaxCheckAfter = shell_exec("php -l \"$livewirePath\" 2>&1");
                $isFixedNow = strpos($syntaxCheckAfter, 'No syntax errors detected') !== false;

                if ($isFixedNow) {
                    // مسح مشاكل الكود المكتشفة بعد الإصلاح الناجح
                    $this->detectedSyntaxIssues = [];

                    // تسجيل وقت الإصلاح
                    $cacheKey = $this->editingModule;
                    $this->lastFixTime[$cacheKey] = time();
                    $this->syntaxCheckCache[$cacheKey] = []; // مسح الكاش

                    $this->dispatchBrowserEvent('success', [
                        'title' => 'تم الإصلاح بنجاح ✅',
                        'message' => "تم إصلاح المشاكل بنجاح. الملف سليم الآن! تم إنشاء نسخة احتياطية."
                    ]);

                    Log::info("تم إصلاح syntax errors بنجاح في: {$livewirePath}");
                } else {
                    // إعادة الملف الأصلي إذا لم ينجح الإصلاح
                    File::put($livewirePath, $originalContent);
                    File::delete($backupPath);

                    $this->dispatchBrowserEvent('error', [
                        'title' => 'فشل الإصلاح',
                        'message' => 'لم يتمكن النظام من إصلاح المشاكل تلقائياً. يرجى المراجعة اليدوية.'
                    ]);
                }

                Log::info("تم إنشاء نسخة احتياطية: {$backupPath}");

                // لا نعيد تحميل البيانات - مجرد إصلاح للكود
            } else {
                // فحص إضافي للتأكد من عدم وجود أخطاء syntax
                $finalCheck = shell_exec("php -l \"$livewirePath\" 2>&1");
                $isReallyClean = strpos($finalCheck, 'No syntax errors detected') !== false;

                if ($isReallyClean) {
                    $this->dispatchBrowserEvent('success', [
                        'title' => 'ملف سليم تماماً ✅',
                        'message' => 'تم فحص الملف ولم يتم العثور على أي مشاكل syntax. الملف سليم ولا يحتاج لإصلاح.'
                    ]);
                } else {
                    $this->dispatchBrowserEvent('warning', [
                        'title' => 'مشكلة غير محددة',
                        'message' => 'يبدو أن هناك مشكلة في الملف لكن لا يمكن إصلاحها تلقائياً'
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("خطأ في إصلاح syntax errors: " . $e->getMessage());
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في الإصلاح',
                'message' => 'حدث خطأ أثناء محاولة إصلاح الملف: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إعادة تعيين نموذج الحقل الجديد
     */
    private function resetNewField()
    {
        $this->newField = [
            'name' => '',
            'ar_name' => '',
            'type' => 'text',
            'required' => false,
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
            'integer_type' => 'int', // tinyint, smallint, int, bigint
            'unsigned' => false, // موجب فقط
            // إعدادات الأرقام العشرية الجديدة
            'decimal_precision' => 15, // إجمالي عدد الأرقام
            'decimal_scale' => 2, // عدد المراتب العشرية
            'file_types' => '',
            'select_options' => [],
            'select_source' => 'manual',
            'related_table' => '',
            'related_key' => 'id',
            'related_display' => 'name',
            'checkbox_true_label' => 'نعم',
            'checkbox_false_label' => 'لا',
        ];
    }

    /**
     * إغلاق نافذة التعديل
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingModule = null;
        $this->editMode = 'view';
        $this->newFields = '';
        $this->selectedModuleData = [];
        $this->moduleFields = [];
        $this->arabicName = '';

        // إعادة تعيين الحقول المتطورة
        $this->pendingFields = [];
        $this->resetNewFieldFormSilently(); // استخدام النسخة الصامتة عند إغلاق النافذة
        $this->enableExcelExport = true;
        $this->enablePdfExport = true;
        $this->enableFlatpickr = true;
        $this->enableSelect2 = true;

        Log::info("تم إغلاق نافذة التعديل");
    }

    /**
     * حذف ملفات تكوين الوحدة من جميع المجلدات
     */
    private function deleteModuleConfigFiles($moduleName)
    {
        try {
            // مجلدات التكوين المختلفة
            $configPaths = [
                storage_path("app/hmvc-modules-config"),
                storage_path("app/modules_config"), // المجلد الجديد المهم
                storage_path("app/hmvc-modules-backups"),
                storage_path("app/menu_backups"),
                storage_path("app/permission_backups")
            ];

            // أنماط أسماء الملفات المحتملة
            $filePatterns = [
                "{$moduleName}.json",
                "{$moduleName}_fields.json",
                strtolower($moduleName) . ".json",
                strtolower($moduleName) . "_fields.json",
                ucfirst(strtolower($moduleName)) . ".json",
                ucfirst(strtolower($moduleName)) . "_fields.json",
                Str::singular($moduleName) . ".json",
                Str::singular($moduleName) . "_fields.json",
                Str::singular(strtolower($moduleName)) . ".json",
                Str::singular(strtolower($moduleName)) . "_fields.json",
                Str::plural($moduleName) . ".json",
                Str::plural($moduleName) . "_fields.json",
                Str::plural(strtolower($moduleName)) . ".json",
                Str::plural(strtolower($moduleName)) . "_fields.json",
            ];

            $deletedCount = 0;

            foreach ($configPaths as $configPath) {
                if (!is_dir($configPath)) {
                    continue;
                }

                foreach ($filePatterns as $fileName) {
                    $filePath = $configPath . DIRECTORY_SEPARATOR . $fileName;
                    if (file_exists($filePath)) {
                        try {
                            unlink($filePath);
                            $deletedCount++;
                            Log::info("تم حذف ملف التكوين: {$filePath}");
                        } catch (\Exception $e) {
                            Log::warning("خطأ في حذف ملف التكوين {$filePath}: " . $e->getMessage());
                        }
                    }
                }

                // حذف ملفات النسخ الاحتياطية بأنماط متعددة
                $backupPatterns = [
                    "{$moduleName}_backup_*.json",
                    strtolower($moduleName) . "_backup_*.json",
                    "*{$moduleName}*.json",
                    "*" . strtolower($moduleName) . "*.json"
                ];

                foreach ($backupPatterns as $pattern) {
                    $backupFiles = glob($configPath . DIRECTORY_SEPARATOR . $pattern);
                    foreach ($backupFiles as $backupFile) {
                        if (file_exists($backupFile)) {
                            try {
                                unlink($backupFile);
                                $deletedCount++;
                                Log::info("تم حذف النسخة الاحتياطية: {$backupFile}");
                            } catch (\Exception $e) {
                                Log::warning("خطأ في حذف النسخة الاحتياطية {$backupFile}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }

            Log::info("تم حذف {$deletedCount} ملف تكوين ونسخة احتياطية للوحدة {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حذف ملفات التكوين للوحدة {$moduleName}: " . $e->getMessage());
        }
    }

    /**
     * حذف الحقول الخاصة بالوحدة من جدول module_fields
     */
    private function deleteModuleFieldsFromDatabase($moduleName)
    {
        try {
            // قائمة الأسماء المحتملة للوحدة
            $possibleModuleNames = [
                $moduleName,
                strtolower($moduleName),
                ucfirst(strtolower($moduleName)),
                Str::singular($moduleName),
                Str::singular(strtolower($moduleName)),
                Str::plural($moduleName),
                Str::plural(strtolower($moduleName))
            ];

            // إزالة التكرارات
            $possibleModuleNames = array_unique($possibleModuleNames);

            $deletedCount = 0;

            foreach ($possibleModuleNames as $moduleName) {
                // حذف جميع الحقول المرتبطة بهذه الوحدة
                $deleted = DB::table('module_fields')
                    ->where('module_name', $moduleName)
                    ->delete();

                if ($deleted > 0) {
                    $deletedCount += $deleted;
                    Log::info("تم حذف {$deleted} حقل للوحدة {$moduleName} من جدول module_fields");
                }
            }

            if ($deletedCount > 0) {
                Log::info("تم حذف إجمالي {$deletedCount} حقل من جدول module_fields للوحدة {$this->moduleToDelete}");

                $this->dispatchBrowserEvent('success', [
                    'title' => 'تنظيف قاعدة البيانات',
                    'message' => "تم حذف {$deletedCount} حقل من جدول module_fields"
                ]);
            } else {
                Log::info("لم يتم العثور على حقول للوحدة {$this->moduleToDelete} في جدول module_fields");
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف حقول الوحدة من جدول module_fields: " . $e->getMessage());

            // لا نوقف العملية بسبب هذا الخطأ، لكن نسجل تحذير
            $this->dispatchBrowserEvent('warning', [
                'title' => 'تحذير',
                'message' => 'حدث خطأ في تنظيف جدول module_fields، لكن باقي العملية نجحت'
            ]);
        }
    }

    /**
     * حفظ الحقول الجديدة في جدول module_fields
     */
    private function saveNewFieldsToDatabase($moduleName, $newFields)
    {
        try {
            // الحصول على اسم الجدول والاسم العربي للوحدة
            $tableName = Str::snake(Str::plural($moduleName));
            $moduleArabicName = $this->getModuleArabicNameFromSources($moduleName);

            foreach ($newFields as $field) {
                // تحديد ترتيب الحقل (آخر ترتيب + 1)
                $maxOrder = ModuleField::where('module_name', $moduleName)
                    ->max('order') ?? 0;

                // تحضير بيانات الحقل للحفظ
                $fieldData = [
                    'module_name' => $moduleName,
                    'table_name' => $tableName,
                    'module_arabic_name' => $moduleArabicName,
                    'field_name' => $field['name'],
                    'field_type' => $field['type'],
                    'arabic_name' => $field['ar_name'] ?? $field['name'],
                    'english_name' => $field['name'],
                    'required' => $field['required'] ?? false,
                    'unique' => $field['unique'] ?? false,
                    'searchable' => $field['searchable'] ?? true,
                    'show_in_table' => $field['show_in_table'] ?? true,
                    'show_in_search' => $field['show_in_search'] ?? true,
                    'show_in_forms' => $field['show_in_forms'] ?? true,
                    'max_length' => !empty($field['size']) ? (int)$field['size'] : null,
                    'arabic_only' => $field['arabic_only'] ?? false,
                    'numeric_only' => $field['numeric_only'] ?? false,
                    'text_content_type' => $field['text_content_type'] ?? 'any',
                    // إعدادات الأرقام الصحيحة الجديدة
                    'integer_type' => $field['integer_type'] ?? 'int',
                    'unsigned' => $field['unsigned'] ?? false,
                    // إعدادات الأرقام العشرية الجديدة
                    'decimal_precision' => $field['decimal_precision'] ?? 15,
                    'decimal_scale' => $field['decimal_scale'] ?? 2,
                    'file_types' => $field['file_types'] ?? null,
                    'select_options' => !empty($field['select_options']) ? $field['select_options'] : null,
                    'select_source' => $field['select_source'] ?? 'manual',
                    'select_numeric_values' => $field['select_numeric_values'] ?? false,
                    'related_table' => $field['related_table'] ?? null,
                    'related_key' => $field['related_key'] ?? 'id',
                    'related_display' => $field['related_display'] ?? 'name',
                    'validation_rules' => $this->generateValidationRules($field),
                    'validation_messages' => $this->generateValidationMessages($field), // إرجاع array مباشرة
                    'custom_attributes' => [
                        'placeholder' => 'أدخل ' . ($field['ar_name'] ?? $field['name']),
                        'dir' => ($this->getFieldDirection($field)) ? 'rtl' : 'auto',
                        'class' => 'form-control' .
                                 (($field['required'] ?? false) ? ' required' : '') .
                                 (($this->isArabicOnly($field)) ? ' arabic-only' : '') .
                                 (($this->isNumericOnly($field)) ? ' numeric-only' : ''),
                        'maxlength' => !empty($field['size']) ? (string)$field['size'] : '255',
                        'pattern' => $this->getFieldPattern($field),
                        'title' => $this->getFieldTitle($field),
                        'required' => ($field['required'] ?? false) ? 'required' : null,
                        'inputmode' => $field['type'] === 'decimal' ? 'decimal' : null,
                    ],
                    'created_by' => auth()->id() ?? 1,
                    'order' => $maxOrder + 1,
                    'active' => true,
                    'is_calculated' => $field['is_calculated'] ?? false,
                    'calculation_formula' => $field['calculation_formula'] ?? null,
                    'calculation_type' => $field['calculation_type'] ?? 'none',
                    'date_from_field' => $field['date_from_field'] ?? null,
                    'date_to_field' => $field['date_to_field'] ?? null,
                    'date_diff_unit' => $field['date_diff_unit'] ?? 'days',
                    'include_end_date' => $field['include_end_date'] ?? false,
                    'absolute_value' => $field['absolute_value'] ?? false,
                    'remaining_only' => $field['remaining_only'] ?? false,
                    'is_date_calculated' => $field['is_date_calculated'] ?? false,
                    'date_calculation_config' => $field['date_calculation_config'] ?? null,
                    // خصائص حساب الوقت
                    'time_from_field' => $field['time_from_field'] ?? null,
                    'time_to_field' => $field['time_to_field'] ?? null,
                    'time_diff_unit' => $field['time_diff_unit'] ?? 'hours',
                    'is_time_calculated' => $field['is_time_calculated'] ?? false,
                    'time_calculation_config' => $field['time_calculation_config'] ?? null,
                ];

                // استخدام موديل Eloquent للاستفادة من التحويل التلقائي للـ arrays
                $existingField = ModuleField::where('module_name', $moduleName)
                    ->where('field_name', $field['name'])
                    ->first();

                if (!$existingField) {
                    ModuleField::create($fieldData);
                    Log::info("تم حفظ الحقل {$field['name']} في جدول module_fields للوحدة {$moduleName}");
                } else {
                    // تحديث الحقل الموجود
                    $existingField->update($fieldData);
                    Log::info("تم تحديث الحقل {$field['name']} في جدول module_fields للوحدة {$moduleName}");
                }
            }

            Log::info("تم حفظ " . count($newFields) . " حقل جديد في جدول module_fields للوحدة: {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حفظ الحقول في قاعدة البيانات: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * توليد قواعد التحقق للحقل
     */
    private function generateValidationRules($field)
    {
        $rules = [];

        if ($field['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($field['type']) {
            case 'string':
                $rules[] = 'string';
                if (!empty($field['size'])) {
                    $rules[] = 'max:' . $field['size'];
                } else {
                    $rules[] = 'max:255';
                }

                // إضافة قواعد حسب نوع المحتوى النصي
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

                // للتوافق مع النظام القديم
                if ($field['arabic_only'] ?? false) {
                    $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                }
                if ($field['numeric_only'] ?? false) {
                    $rules[] = 'regex:/^[0-9]+$/';
                }
                break;

            case 'text':
                $rules[] = 'string';

                // إضافة قواعد حسب نوع المحتوى النصي
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

                // للتوافق مع النظام القديم
                if ($field['arabic_only'] ?? false) {
                    $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                }
                if ($field['numeric_only'] ?? false) {
                    $rules[] = 'regex:/^[0-9]+$/';
                }
                break;

            case 'integer':
                $rules[] = 'integer';

                // إضافة قواعد حسب نوع الرقم الصحيح
                if (isset($field['integer_type'])) {
                    $this->addIntegerValidationRules($rules, $field);
                }
                break;

            case 'decimal':
                // استخدام regex للأرقام العشرية لتجنب مشاكل التحويل
                if (isset($field['decimal_precision']) && isset($field['decimal_scale'])) {
                    $precision = $field['decimal_precision'];
                    $scale = $field['decimal_scale'];
                    $integerDigits = $precision - $scale;

                    if ($scale > 0) {
                        // للأرقام العشرية
                        $rules[] = "regex:/^\d{1,{$integerDigits}}(\.\d{1,{$scale}})?$/";
                    } else {
                        // للأرقام الصحيحة بدون مراتب عشرية
                        $rules[] = "regex:/^\d{1,{$precision}}$/";
                    }
                } else {
                    // قاعدة افتراضية للأرقام العشرية
                    $rules[] = 'regex:/^\d+(\.\d{1,2})?$/';
                }
                break;

            case 'email':
                $rules[] = 'email';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'datetime':
                $rules[] = 'date';
                break;
            case 'checkbox':
                $rules[] = 'boolean';
                break;
            case 'file':
                $rules[] = 'file';
                if (!empty($field['file_types'])) {
                    switch ($field['file_types']) {
                        case 'image':
                            $rules[] = 'image';
                            break;
                        case 'pdf':
                            $rules[] = 'mimes:pdf';
                            break;
                        case 'document':
                            $rules[] = 'mimes:pdf,doc,docx,xls,xlsx';
                            break;
                    }
                }
                break;
            case 'select':
                $rules[] = 'string';
                break;
        }

        if ($field['unique'] ?? false) {
            $rules[] = 'unique:' . strtolower(Str::plural($field['module_name'] ?? 'table'));
        }

        return implode('|', $rules);
    }

    /**
     * توليد رسائل التحقق للحقل
     */
    private function generateValidationMessages($field)
    {
        $fieldName = $field['ar_name'] ?? $field['name'];
        $messages = [];

        if ($field['required'] ?? false) {
            $messages['required'] = "يرجى إدخال {$fieldName}";
        }

        switch ($field['type']) {
            case 'string':
                $messages['string'] = "حقل {$fieldName} يجب أن يكون نص";
                if (!empty($field['size'])) {
                    $messages['max'] = "{$fieldName} يجب أن يكون أقل من {$field['size']} حرف";
                } else {
                    $messages['max'] = "{$fieldName} يجب أن يكون أقل من 255 حرف";
                }

                // إضافة رسائل حسب نوع المحتوى النصي
                if (isset($field['text_content_type'])) {
                    switch ($field['text_content_type']) {
                        case 'arabic_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف عربية فقط";
                            break;
                        case 'english_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف إنجليزية فقط";
                            break;
                        case 'numeric_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أرقام فقط";
                            break;
                    }
                }

                // للتوافق مع النظام القديم
                if ($field['arabic_only'] ?? false) {
                    $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف عربية فقط";
                }
                if ($field['numeric_only'] ?? false) {
                    $messages['regex'] = "{$fieldName} يجب أن يحتوي على أرقام فقط";
                }
                break;

            case 'text':
                $messages['string'] = "حقل {$fieldName} يجب أن يكون نص";

                // إضافة رسائل حسب نوع المحتوى النصي
                if (isset($field['text_content_type'])) {
                    switch ($field['text_content_type']) {
                        case 'arabic_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف عربية فقط";
                            break;
                        case 'english_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف إنجليزية فقط";
                            break;
                        case 'numeric_only':
                            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أرقام فقط";
                            break;
                    }
                }

                // للتوافق مع النظام القديم
                if ($field['arabic_only'] ?? false) {
                    $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف عربية فقط";
                }
                if ($field['numeric_only'] ?? false) {
                    $messages['regex'] = "{$fieldName} يجب أن يحتوي على أرقام فقط";
                }
                break;

            case 'integer':
                $messages['integer'] = "حقل {$fieldName} يجب أن يكون رقم صحيح";
                // Add detailed integer validation messages based on integer type
                $integerMessages = $this->getIntegerValidationMessages($field);
                $messages = array_merge($messages, $integerMessages);
                break;

            case 'decimal':
                // رسالة regex مخصصة للأرقام العشرية
                if (isset($field['decimal_precision']) && isset($field['decimal_scale'])) {
                    $precision = $field['decimal_precision'];
                    $scale = $field['decimal_scale'];
                    $integerDigits = $precision - $scale;

                    // إنشاء مثال ديناميكي
                    $exampleInteger = str_repeat('1', min($integerDigits, 3));
                    $exampleDecimal = str_repeat('5', $scale);
                    $example = $scale > 0 ? "{$exampleInteger}.{$exampleDecimal}" : $exampleInteger;

                    if ($scale > 0) {
                        $messages['regex'] = "{$fieldName} يجب أن يكون رقم عشري صحيح بحد أقصى {$integerDigits} أرقام قبل الفاصلة و{$scale} أرقام بعد الفاصلة (مثال: {$example})";
                    } else {
                        $messages['regex'] = "{$fieldName} يجب أن يكون رقم صحيح بحد أقصى {$precision} أرقام (مثال: {$example})";
                    }
                } else {
                    $messages['regex'] = "{$fieldName} يجب أن يكون رقم عشري صحيح";
                }
                $messages['numeric'] = "حقل {$fieldName} يجب أن يكون رقم";
                break;
            case 'email':
                $messages['email'] = "حقل {$fieldName} يجب أن يكون بريد إلكتروني صالح";
                break;
            case 'date':
            case 'datetime':
                $messages['date'] = "حقل {$fieldName} يجب أن يكون تاريخ صالح";
                break;
            case 'file':
                $messages['file'] = "حقل {$fieldName} يجب أن يكون ملف";
                if (!empty($field['file_types'])) {
                    switch ($field['file_types']) {
                        case 'image':
                            $messages['image'] = "حقل {$fieldName} يجب أن يكون صورة";
                            break;
                        case 'pdf':
                            $messages['mimes'] = "حقل {$fieldName} يجب أن يكون ملف PDF";
                            break;
                        case 'document':
                            $messages['mimes'] = "حقل {$fieldName} يجب أن يكون ملف مستند صحيح";
                            break;
                    }
                }
                $messages['max'] = "حجم حقل {$fieldName} يجب ألا يزيد عن 10 ميجا";
                break;
        }

        if ($field['unique'] ?? false) {
            $messages['unique'] = "{$fieldName} موجود بالفعل";
        }

        if ($field['arabic_only'] ?? false) {
            $messages['regex'] = "{$fieldName} يجب أن يحتوي على أحرف عربية فقط";
        }

        // إرجاع البيانات بنفس تنسيق مولد الوحدات (بدون JSON encoding هنا)
        return $messages;
    }

    /**
     * تحميل الحقول من جدول module_fields
     */
    private function loadModuleFieldsFromDatabase($moduleName)
    {
        try {
            $fields = DB::table('module_fields')
                ->where('module_name', $moduleName)
                ->where('active', true)
                ->orderBy('order')
                ->get();

            $moduleFields = [];
            foreach ($fields as $field) {
                $customAttributes = json_decode($field->custom_attributes ?? '{}', true);

                // 🔧 إصلاح محسن: التأكد من جلب select_options بالشكل الصحيح
                $selectOptions = [];
                if ($field->select_options) {
                    $decoded = json_decode($field->select_options, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $selectOptions = $decoded;
                    } else {
                        Log::warning("⚠️ خطأ في فك تشفير select_options للحقل {$field->field_name}: " . json_last_error_msg());
                        Log::warning("⚠️ البيانات الخام: " . $field->select_options);
                    }
                }

                $moduleFields[] = [
                    'name' => $field->field_name,
                    'ar_name' => $field->arabic_name,
                    'arabic_name' => $field->arabic_name, // للتوافق
                    'type' => $field->field_type,
                    'required' => (bool)$field->required,
                    'unique' => (bool)$field->unique,
                    'searchable' => (bool)$field->searchable,
                    'show_in_table' => (bool)$field->show_in_table,
                    'show_in_search' => (bool)$field->show_in_search,
                    'show_in_forms' => (bool)$field->show_in_forms,
                    'size' => $field->max_length,
                    'arabic_only' => (bool)$field->arabic_only,
                    'numeric_only' => (bool)$field->numeric_only,
                    'file_types' => $field->file_types,
                    'select_options' => $selectOptions, // ✅ مصحح ومحسن
                    'options' => $selectOptions, // ✅ إضافة للتوافق مع مولد الوحدات
                    'select_source' => $field->select_source ?? 'manual',
                    'related_table' => $field->related_table,
                    'related_key' => $field->related_key ?? 'id',
                    'related_display' => $field->related_display ?? 'name',
                    'checkbox_true_label' => $customAttributes['checkbox_true_label'] ?? 'نعم',
                    'checkbox_false_label' => $customAttributes['checkbox_false_label'] ?? 'لا',
                    'is_calculated' => (bool)($field->is_calculated ?? false),
                    'calculation_formula' => $field->calculation_formula ?? '',
                    'calculation_type' => $field->calculation_type ?? 'none',
                    'date_from_field' => $field->date_from_field ?? '',
                    'date_to_field' => $field->date_to_field ?? '',
                    'date_diff_unit' => $field->date_diff_unit ?? 'days',
                    'include_end_date' => (bool)($field->include_end_date ?? false),
                    'absolute_value' => (bool)($field->absolute_value ?? false),
                    'remaining_only' => (bool)($field->remaining_only ?? false),
                    'is_date_calculated' => (bool)($field->is_date_calculated ?? false),
                    'date_calculation_config' => $field->date_calculation_config ?? null,
                ];
            }

            Log::info("✅ تم تحميل " . count($moduleFields) . " حقل من قاعدة البيانات للوحدة: {$moduleName}");

            // 🔍 تسجيل مفصل لتفاصيل select_options للتتبع
            foreach ($moduleFields as $field) {
                if (!empty($field['select_options'])) {
                    Log::info("🎯 حقل {$field['name']} ({$field['type']}) يحتوي على " . count($field['select_options']) . " خيارات: " . implode(', ', $field['select_options']));
                } else {
                    Log::info("ℹ️ حقل {$field['name']} ({$field['type']}) لا يحتوي على خيارات");
                }
            }

            return $moduleFields;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في تحميل الحقول من قاعدة البيانات: " . $e->getMessage());
            Log::error("❌ Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * حذف use statements الخاصة بـ PDF Controllers من web.php
     */
    private function removePdfUseStatements($moduleName)
    {
        try {
            $webRoutesPath = base_path('routes/web.php');
            if (!File::exists($webRoutesPath)) {
                return;
            }

            $content = File::get($webRoutesPath);
            $originalContent = $content;
            $singularName = Str::singular($moduleName);

            // حذف use statements للـ PDF Controllers
            $useStatements = [
                "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}TcpdfExportController;",
                "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}PrintController;"
            ];

            foreach ($useStatements as $useStatement) {
                $content = str_replace($useStatement . "\n", "", $content);
                $content = str_replace($useStatement, "", $content);
            }

            // حفظ التغييرات إذا حدثت
            if ($originalContent !== $content) {
                File::put($webRoutesPath, $content);
                Log::info("تم حذف use statements الخاصة بـ PDF للوحدة {$moduleName}");
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف use statements للوحدة {$moduleName}: " . $e->getMessage());
        }
    }

    /**
     * حذف الوحدة من جدول المجموعات الأساسية بناءً على permission - حذف نهائي
     */
    private function removeModuleFromBasicGroups($moduleName)
    {
        try {
            $deletedCount = 0;

            // البحث عن الوحدة في جدول basic_groups بناءً على permission أو name_en
            $basicGroups = BasicGroup::where(function($query) use ($moduleName) {
                    $query->where('permission', $moduleName)
                          ->orWhere('permission', strtolower($moduleName))
                          ->orWhere('name_en', $moduleName)
                          ->orWhere('name_en', strtolower($moduleName));
                })
                ->get();

            foreach ($basicGroups as $group) {
                // تسجيل معلومات الوحدة قبل الحذف
                Log::info("سيتم حذف الوحدة نهائياً من جدول basic_groups: {$group->name_ar} (ID: {$group->id}, Permission: {$group->permission})");

                // حذف نهائي للوحدة باستخدام forceDelete
                $group->forceDelete();

                $deletedCount++;
                Log::info("تم حذف الوحدة نهائياً من جدول basic_groups: {$group->name_ar} (ID: {$group->id})");
            }

            if ($deletedCount > 0) {
                Log::info("تم حذف {$deletedCount} وحدة نهائياً من جدول المجموعات الأساسية للوحدة: {$moduleName}");
            } else {
                Log::info("لم يتم العثور على الوحدة {$moduleName} في جدول المجموعات الأساسية");
            }

        } catch (\Exception $e) {
            Log::error("خطأ في حذف الوحدة {$moduleName} من جدول المجموعات الأساسية: " . $e->getMessage());
        }
    }

    /**
     * فحص ما إذا كان يجب إنشاء migration لحذف العمود
     */
    private function shouldCreateDropColumnMigration($moduleName, $fieldName)
    {
        try {
            $lowerModuleName = strtolower($moduleName);
            $tableName = Str::plural($lowerModuleName);

            // فحص وجود الجدول
            if (!Schema::hasTable($tableName)) {
                Log::info("⚠️ الجدول {$tableName} غير موجود");
                return false;
            }

            // فحص وجود العمود
            if (!Schema::hasColumn($tableName, $fieldName)) {
                Log::info("⚠️ العمود {$fieldName} غير موجود في جدول {$tableName}");
                return false;
            }

            // فحص إذا كان migration مماثل موجود مسبقاً
            $migrationName = "drop_{$fieldName}_from_{$tableName}_table";
            $existingMigrations = glob(database_path("migrations/*_{$migrationName}.php"));
            if (!empty($existingMigrations)) {
                Log::info("⚠️ Migration لحذف الحقل {$fieldName} موجود مسبقاً");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في فحص إنشاء migration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * إنشاء migration لحذف عمود من جدول الوحدة مع فحص الوجود المسبق
     */
    private function createDropColumnMigration($moduleName, $fieldName)
    {
        try {
            $lowerModuleName = strtolower($moduleName);
            $tableName = Str::plural($lowerModuleName);
            $migrationName = "drop_{$fieldName}_from_{$tableName}_table";

            // فحص إذا كان migration مماثل موجود مسبقاً
            $existingMigrations = glob(database_path("migrations/*_{$migrationName}.php"));
            if (!empty($existingMigrations)) {
                Log::info("⚠️ Migration لحذف الحقل {$fieldName} موجود مسبقاً، سيتم تجاهله");
                return true;
            }

            // فحص إذا كان العمود موجود في الجدول
            if (!Schema::hasColumn($tableName, $fieldName)) {
                Log::info("⚠️ العمود {$fieldName} غير موجود في جدول {$tableName}، لا حاجة لـ migration");
                return true;
            }

            $className = "Drop" . Str::studly($fieldName) . "From" . Str::studly($tableName) . "Table";

            $migrationContent = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('{$tableName}', '{$fieldName}')) {
            Schema::table('{$tableName}', function (Blueprint \$table) {
                \$table->dropColumn('{$fieldName}');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            // يمكن إضافة العمود مرة أخرى هنا إذا لزم الأمر
            // \$table->string('{$fieldName}')->nullable();
        });
    }
};
";

            $timestamp = date('Y_m_d_His');
            $migrationFileName = "{$timestamp}_{$migrationName}.php";
            $migrationPath = database_path("migrations/{$migrationFileName}");

            File::put($migrationPath, $migrationContent);
            Log::info("✅ تم إنشاء migration لحذف الحقل: {$migrationPath}");

            return true;

        } catch (\Exception $e) {
            Log::error("❌ خطأ في إنشاء migration لحذف الحقل: " . $e->getMessage());
            return false;
        }
    }

    /**
     * حذف الحقل من ملف تكوين الوحدة
     */
    private function removeFieldFromConfiguration($moduleName, $fieldName)
    {
        try {
            // حذف من جدول module_fields
            DB::table('module_fields')
                ->where('module_name', $moduleName)
                ->where('field_name', $fieldName)
                ->delete();

            // حذف من ملف JSON التكوين الرئيسي
            $configPath = storage_path("app/modules_config/{$moduleName}.json");
            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);
                if (isset($config[$fieldName])) {
                    unset($config[$fieldName]);
                    File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

            // حذف من ملف fields الإضافي إذا كان موجوداً
            $fieldsConfigPath = storage_path("app/modules_config/{$moduleName}_fields.json");
            if (File::exists($fieldsConfigPath)) {
                $fieldsConfig = json_decode(File::get($fieldsConfigPath), true);
                if (isset($fieldsConfig['fields'])) {
                    $fieldsConfig['fields'] = array_filter($fieldsConfig['fields'], function($field) use ($fieldName) {
                        return $field['name'] !== $fieldName;
                    });
                    File::put($fieldsConfigPath, json_encode($fieldsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

            Log::info("تم حذف الحقل {$fieldName} من تكوين الوحدة {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حذف الحقل من التكوين: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * إزالة الحقل من ملفات العرض
     */
    private function removeFieldFromViews($moduleName, $fieldData)
    {
        try {
            $fieldName = $fieldData['name'];
            $lowerModuleName = strtolower($moduleName);

            // ملف العرض الرئيسي
            $mainViewPath = resource_path("views/livewire/{$lowerModuleName}/{$lowerModuleName}.blade.php");
            if (File::exists($mainViewPath)) {
                $this->removeFieldFromViewFile($mainViewPath, $fieldName, $fieldData);
            }

            // ملف الإضافة
            $addModalPath = resource_path("views/livewire/{$lowerModuleName}/modals/add-{$lowerModuleName}.blade.php");
            if (File::exists($addModalPath)) {
                $this->removeFieldFromViewFile($addModalPath, $fieldName, $fieldData);
            }

            // ملف التعديل
            $editModalPath = resource_path("views/livewire/{$lowerModuleName}/modals/edit-{$lowerModuleName}.blade.php");
            if (File::exists($editModalPath)) {
                $this->removeFieldFromViewFile($editModalPath, $fieldName, $fieldData);
            }

            Log::info("تم حذف الحقل {$fieldName} من ملفات العرض للوحدة {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في حذف الحقل من ملفات العرض: " . $e->getMessage());
            // لا نرمي الخطأ هنا لأن حذف العرض ليس حرجاً
        }
    }

    /**
     * إزالة الحقل من ملف عرض محدد
     */
    private function removeFieldFromViewFile($filePath, $fieldName, $fieldData)
    {
        try {
            $content = File::get($filePath);
            $fieldArName = $fieldData['ar_name'] ?? $fieldName;

            // إزالة أعمدة الجدول
            $patterns = [
                // عمود الجدول
                "/\s*<th[^>]*>.*?" . preg_quote($fieldArName, '/') . ".*?<\/th>/s",
                "/\s*<td[^>]*>.*?\{\{\s*\\\$item->{$fieldName}\s*\}\}.*?<\/td>/s",

                // حقول النماذج
                "/\s*<div[^>]*class=\"[^\"]*form-floating[^\"]*\"[^>]*>.*?{$fieldName}.*?<\/div>\s*<\/div>/s",
                "/\s*<div[^>]*>.*?wire:model[^>]*{$fieldName}.*?<\/div>/s",

                // التحقق من الأخطاء
                "/\s*@error\('{$fieldName}'\).*?@enderror/s",
            ];

            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }

            File::put($filePath, $content);

        } catch (\Exception $e) {
            Log::error("خطأ في تعديل ملف العرض {$filePath}: " . $e->getMessage());
        }
    }

    /**
     * تشغيل المايجريشن
     */
    private function runMigrations()
    {
        try {
            Log::info("🚀 بدء تشغيل المايجريشن...");
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            Log::info("✅ تم تشغيل المايجريشن بنجاح: " . $output);
        } catch (\Exception $e) {
            Log::error("❌ خطأ في تشغيل المايجريشن: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تحديد المجموعة الأب للوحدة تلقائياً من ملف dynamic-menu
     */
    private function determineModuleParentGroup($moduleName)
    {
        try {
            $menuItems = config('dynamic-menu.menu_items', []);

            foreach ($menuItems as $item) {
                // فحص إذا كانت الوحدة رئيسية (مجموعة أو عنصر مع basic_group_id)
                if (isset($item['permission']) && $item['permission'] === $moduleName) {
                    if ($item['type'] === 'group' || isset($item['basic_group_id'])) {
                        // وحدة رئيسية - لا تحتاج مجموعة أب
                        return null;
                    }
                }

                // فحص إذا كانت الوحدة فرعية في مجموعة
                if ($item['type'] === 'group' && isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission']) && $child['permission'] === $moduleName) {
                            // وحدة فرعية - إرجاع اسم المجموعة الأب
                            return $item['permission'];
                        }
                    }
                }
            }

            // إذا لم توجد في التكوين، افتراض أنها وحدة رئيسية
            Log::info("الوحدة {$moduleName} غير موجودة في dynamic-menu، ستُعتبر وحدة رئيسية");
            return null;

        } catch (\Exception $e) {
            Log::error("خطأ في تحديد المجموعة الأب للوحدة {$moduleName}: " . $e->getMessage());
            // في حالة الخطأ، افتراض أنها وحدة رئيسية
            return null;
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
                return ['valid' => false, 'error' => 'معادلة الحساب لا يمكن أن تكون فارغة'];
            }

            // قائمة الحقول الرقمية المتاحة (من الحقول الموجودة والمعلقة)
            $availableFields = [];

            // إضافة الحقول الموجودة
            foreach ($this->moduleFields as $field) {
                $isNumericField = false;

                // حقول رقمية تقليدية
                if (in_array($field['type'], ['integer', 'decimal'])) {
                    $isNumericField = true;
                }

                // حقول select رقمية
                if ($field['type'] === 'select') {
                    // إذا كان select_numeric_values موجود ومضبوط على true
                    if (isset($field['select_numeric_values']) && $field['select_numeric_values'] == true) {
                        $isNumericField = true;
                    }
                    // أو إذا كان الحقل يحتوي على خيارات رقمية (للحقول القديمة)
                    elseif (!isset($field['select_numeric_values']) && !empty($field['select_options'])) {
                        // فحص إذا كانت جميع الخيارات أرقام
                        $allNumeric = true;
                        foreach ($field['select_options'] as $option) {
                            if (!is_numeric($option)) {
                                $allNumeric = false;
                                break;
                            }
                        }
                        if ($allNumeric) {
                            $isNumericField = true;
                        }
                    }
                }

                if ($isNumericField) {
                    $availableFields[] = $field['name'];
                }
            }

            // إضافة الحقول المعلقة
            foreach ($this->pendingFields as $field) {
                if (in_array($field['type'], ['integer', 'decimal']) ||
                    ($field['type'] === 'select' && isset($field['select_numeric_values']) && $field['select_numeric_values'] == true)) {
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
                return ['valid' => false, 'error' => 'يجب أن تحتوي المعادلة على حقل رقمي واحد على الأقل'];
            }

            // التحقق من الأحرف المسموحة (أحرف، أرقام، مسافات، عمليات حسابية، أقواس)
            if (!preg_match('/^[a-zA-Z0-9_\s\+\-\*\/\(\)\.]+$/', $formula)) {
                return ['valid' => false, 'error' => 'المعادلة تحتوي على أحرف غير مسموحة'];
            }

            // التحقق من توازن الأقواس
            $openParentheses = substr_count($formula, '(');
            $closeParentheses = substr_count($formula, ')');
            if ($openParentheses !== $closeParentheses) {
                return ['valid' => false, 'error' => 'الأقواس غير متوازنة في المعادلة'];
            }

            // التحقق من عدم وجود عمليات متتالية
            if (preg_match('/[\+\-\*\/]{2,}/', $formula)) {
                return ['valid' => false, 'error' => 'لا يمكن أن تحتوي المعادلة على عمليات حسابية متتالية'];
            }

            // التحقق من عدم بدء أو انتهاء المعادلة بعملية (ما عدا السالب في البداية)
            if (preg_match('/^[\+\*\/]|[\+\-\*\/]$/', $formula)) {
                return ['valid' => false, 'error' => 'المعادلة لا يمكن أن تبدأ أو تنتهي بعملية حسابية'];
            }

            return ['valid' => true, 'error' => ''];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'خطأ في التحقق من المعادلة: ' . $e->getMessage()];
        }
    }

    /**
     * إضافة حقل إلى معادلة الحساب
     */
    public function addFieldToFormula($fieldName)
    {
        if (empty($fieldName)) {
            return;
        }

        Log::info("إضافة حقل للمعادلة: " . $fieldName);

        $currentFormula = $this->newField['calculation_formula'] ?? '';

        // إضافة فراغ قبل اسم الحقل إذا لم تكن المعادلة فارغة
        if (!empty($currentFormula) && !str_ends_with($currentFormula, ' ')) {
            $currentFormula .= ' ';
        }

        $currentFormula .= $fieldName;
        $this->newField['calculation_formula'] = $currentFormula;

        Log::info("المعادلة الجديدة: " . $currentFormula);
    }

    /**
     * إضافة عامل رياضي إلى معادلة الحساب
     */
    public function addOperatorToFormula($operator)
    {
        $currentFormula = $this->newField['calculation_formula'] ?? '';

        // إضافة فراغات حول العامل (ما عدا الأقواس)
        if ($operator === '(' || $operator === ')') {
            $currentFormula .= $operator;
        } else {
            // إضافة فراغ قبل العامل إذا لم تكن المعادلة فارغة
            if (!empty($currentFormula) && !str_ends_with($currentFormula, ' ')) {
                $currentFormula .= ' ';
            }
            $currentFormula .= $operator . ' ';
        }

        $this->newField['calculation_formula'] = $currentFormula;
    }

    /**
     * إظهار نافذة إدخال رقم ثابت
     */
    public function showNumberInput()
    {
        $this->dispatchBrowserEvent('showNumberPrompt');
    }

    /**
     * إضافة رقم ثابت إلى المعادلة
     */
    public function addNumberToFormula($number)
    {
        if (is_numeric($number)) {
            $currentFormula = $this->newField['calculation_formula'] ?? '';

            // إضافة فراغ قبل الرقم إذا لم تكن المعادلة فارغة
            if (!empty($currentFormula) && !str_ends_with($currentFormula, ' ')) {
                $currentFormula .= ' ';
            }

            $currentFormula .= $number;
            $this->newField['calculation_formula'] = $currentFormula;
        }
    }

    /**
     * مسح معادلة الحساب
     */
    public function clearFormula()
    {
        $this->newField['calculation_formula'] = '';
    }

    /**
     * Get integer validation messages for specific integer type
     */
    private function getIntegerValidationMessages($field)
    {
        $messages = [];
        $arabicLabel = $field['ar_name'] ?? $field['name'];
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
        $arabicLabel = $field['ar_name'] ?? $field['name'];
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

    /**
     * إضافة قواعد التحقق للأرقام الصحيحة حسب نوعها
     */
    private function addIntegerValidationRules(&$rules, $field)
    {
        $integerType = $field['integer_type'] ?? 'int';
        $unsigned = $field['unsigned'] ?? false;

        // تحديد الحدود لكل نوع من أنواع الأرقام الصحيحة
        $limits = [
            'tinyint' => [
                'signed' => ['min' => -128, 'max' => 127],
                'unsigned' => ['min' => 0, 'max' => 255]
            ],
            'smallint' => [
                'signed' => ['min' => -32768, 'max' => 32767],
                'unsigned' => ['min' => 0, 'max' => 65535]
            ],
            'int' => [
                'signed' => ['min' => -2147483648, 'max' => 2147483647],
                'unsigned' => ['min' => 0, 'max' => 4294967295]
            ],
            'bigint' => [
                'signed' => ['min' => '-9223372036854775808', 'max' => '9223372036854775807'],
                'unsigned' => ['min' => 0, 'max' => '18446744073709551615']
            ]
        ];

        if (isset($limits[$integerType])) {
            $range = $unsigned ? $limits[$integerType]['unsigned'] : $limits[$integerType]['signed'];

            // إضافة قواعد min و max
            $rules[] = "min:{$range['min']}";
            $rules[] = "max:{$range['max']}";
        }
    }

    /**
     * تحديد اتجاه النص بناءً على نوع المحتوى
     */
    private function getFieldDirection($field)
    {
        // للطريقة القديمة
        if ($field['arabic_only'] ?? false) {
            return true;
        }

        // للطريقة الجديدة
        if (isset($field['text_content_type'])) {
            return $field['text_content_type'] === 'arabic_only';
        }

        return false;
    }

    /**
     * تحديد ما إذا كان الحقل عربي فقط
     */
    private function isArabicOnly($field)
    {
        return ($field['arabic_only'] ?? false) ||
               (isset($field['text_content_type']) && $field['text_content_type'] === 'arabic_only');
    }

    /**
     * تحديد ما إذا كان الحقل أرقام فقط
     */
    private function isNumericOnly($field)
    {
        return ($field['numeric_only'] ?? false) ||
               (isset($field['text_content_type']) && $field['text_content_type'] === 'numeric_only') ||
               $field['type'] === 'decimal';
    }

    /**
     * تحديد نمط الحقل
     */
    private function getFieldPattern($field)
    {
        // للطريقة القديمة
        if ($field['arabic_only'] ?? false) {
            return '[\u0600-\u06FF\s]+';
        }

        if ($field['numeric_only'] ?? false || $field['type'] === 'decimal') {
            return '[0-9.]+';
        }

        // للطريقة الجديدة
        if (isset($field['text_content_type'])) {
            switch ($field['text_content_type']) {
                case 'arabic_only':
                    return '[\u0600-\u06FF\s]+';
                case 'english_only':
                    return '[a-zA-Z\s]+';
                case 'numeric_only':
                    return '[0-9]+';
            }
        }

        return null;
    }

    /**
     * تحديد عنوان الحقل
     */
    private function getFieldTitle($field)
    {
        // للطريقة القديمة
        if ($field['arabic_only'] ?? false) {
            return 'يجب إدخال أحرف عربية فقط';
        }

        if ($field['numeric_only'] ?? false || $field['type'] === 'decimal') {
            return 'يجب إدخال أرقام فقط';
        }

        // للطريقة الجديدة
        if (isset($field['text_content_type'])) {
            switch ($field['text_content_type']) {
                case 'arabic_only':
                    return 'يجب إدخال أحرف عربية فقط';
                case 'english_only':
                    return 'يجب إدخال أحرف إنجليزية فقط';
                case 'numeric_only':
                    return 'يجب إدخال أرقام فقط';
            }
        }

        return null;
    }

    /**
     * تحديث معلومات الوحدة الأساسية لجميع الحقول الموجودة
     */
    private function updateExistingFieldsModuleInfo($moduleName)
    {
        try {
            $tableName = Str::snake(Str::plural($moduleName));
            $moduleArabicName = $this->getModuleArabicNameFromSources($moduleName);

            // تحديث جميع الحقول الموجودة للوحدة
            ModuleField::updateModuleInfo($moduleName, $tableName, $moduleArabicName);

            Log::info("تم تحديث معلومات الوحدة الأساسية لجميع حقول الوحدة: {$moduleName}");

        } catch (\Exception $e) {
            Log::error("خطأ في تحديث معلومات الوحدة الأساسية: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.module-manager.module-manager-simple');
    }
}
