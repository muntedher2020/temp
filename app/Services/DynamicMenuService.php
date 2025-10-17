<?php

namespace App\Services;

use App\Models\Management\BasicGroup;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class DynamicMenuService
{
    /**
     * تحديث القائمة الديناميكية بإضافة أو تعديل مجموعة أساسية
     */
    public static function updateMenuForGroup(BasicGroup $basicGroup, $action)
    {
        $menuPath = config_path('dynamic-menu.php');
        $menuConfig = require $menuPath;

        switch ($action) {
            case 'create':
                self::addGroupToMenu($menuConfig, $basicGroup);
                self::createPermissionForGroup($basicGroup);
                break;
            case 'update':
                self::updateGroupInMenu($menuConfig, $basicGroup);
                self::updatePermissionForGroup($basicGroup);
                break;
            case 'delete':
                self::removeGroupFromMenu($basicGroup);
                return;
            case 'restore':
                self::addGroupToMenu($menuConfig, $basicGroup);
                self::createPermissionForGroup($basicGroup);
                // استعادة الوحدات الفرعية أيضاً
                self::restoreSubModulesForGroup($menuConfig, $basicGroup);
                break;
            case 'disable':
                self::disableGroupInMenu($menuConfig, $basicGroup);
                self::backupAndDisablePermissions($basicGroup);
                break;
            case 'enable':
                self::enableGroupInMenu($menuConfig, $basicGroup);
                self::restorePermissions($basicGroup);
                // استعادة الوحدات الفرعية عند التفعيل
                self::restoreSubModulesForGroup($menuConfig, $basicGroup);
                break;
        }

        self::saveMenuConfig($menuPath, $menuConfig);
    }

    /**
     * إضافة مجموعة جديدة للقائمة
     */
    private static function addGroupToMenu(&$menuConfig, BasicGroup $basicGroup)
    {
        // لا تضيف المجموعة إذا كانت غير مفعلة
        if (!$basicGroup->status) {
            return;
        }

        // التحقق من عدم وجود المجموعة مسبقاً
        foreach ($menuConfig['menu_items'] as $key => $item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                return; // المجموعة موجودة مسبقاً
            }
        }

        // استخدام النوع المحفوظ في قاعدة البيانات
        $type = $basicGroup->type ?? 'group';

        $newMenuItem = [
            'type' => $type,
            'basic_group_id' => $basicGroup->id,
            'permission' => $basicGroup->permission ?? $basicGroup->name_en,
            'title' => $basicGroup->name_ar, // الاسم العربي للعرض في القائمة
            'icon' => $basicGroup->icon,
            'active_routes' => $basicGroup->active_routes ?
                (is_array($basicGroup->active_routes) ? $basicGroup->active_routes : [$basicGroup->active_routes]) :
                [$basicGroup->name_en]
        ];

        // إضافة children للمجموعات أو route للعناصر المستقلة
        if ($type === 'group') {
            $newMenuItem['children'] = [];
        } else {
            // إضافة route للوحدات المنفردة
            $newMenuItem['route'] = $basicGroup->route ?? $basicGroup->name_en;
        }

        // إدراج المجموعة في الموضع الصحيح حسب sort_order
        $inserted = false;
        for ($i = 0; $i < count($menuConfig['menu_items']); $i++) {
            if (!isset($menuConfig['menu_items'][$i]['basic_group_id'])) {
                continue;
            }

            $currentBasicGroup = BasicGroup::find($menuConfig['menu_items'][$i]['basic_group_id']);
            if ($currentBasicGroup && $basicGroup->sort_order < $currentBasicGroup->sort_order) {
                array_splice($menuConfig['menu_items'], $i, 0, [$newMenuItem]);
                $inserted = true;
                break;
            }
        }

        if (!$inserted) {
            $menuConfig['menu_items'][] = $newMenuItem;
        }
    }

    /**
     * تحديث مجموعة موجودة في القائمة
     */
    private static function updateGroupInMenu(&$menuConfig, BasicGroup $basicGroup)
    {
        foreach ($menuConfig['menu_items'] as $key => $item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                // تحديث النوع والبيانات الأساسية
                $menuConfig['menu_items'][$key]['type'] = $basicGroup->type ?? 'group';
                $menuConfig['menu_items'][$key]['title'] = $basicGroup->name_ar; // الاسم العربي للعرض
                $menuConfig['menu_items'][$key]['icon'] = $basicGroup->icon;
                $menuConfig['menu_items'][$key]['permission'] = $basicGroup->permission ?? $basicGroup->name_en;
                $menuConfig['menu_items'][$key]['active_routes'] = $basicGroup->active_routes ?
                    (is_array($basicGroup->active_routes) ? $basicGroup->active_routes : [$basicGroup->active_routes]) :
                    [$basicGroup->name_en];

                // إضافة route فقط للعناصر المستقلة، وإزالته من المجموعات
                if ($basicGroup->type === 'item') {
                    $menuConfig['menu_items'][$key]['route'] = $basicGroup->route ?? $basicGroup->name_en;
                    // إزالة children إذا وجدت (لأنها عنصر مستقل الآن)
                    unset($menuConfig['menu_items'][$key]['children']);
                } else {
                    // إزالة route إذا وجد (لأنها مجموعة)
                    unset($menuConfig['menu_items'][$key]['route']);
                    // إضافة children إذا لم تكن موجودة
                    if (!isset($menuConfig['menu_items'][$key]['children'])) {
                        $menuConfig['menu_items'][$key]['children'] = [];
                    }
                }
                break;
            }
        }
    }

    /**
     * إزالة مجموعة أساسية من القائمة مع حفظ الوحدات الفرعية للاستعادة لاحقاً
     */
    public static function removeGroupFromMenu(BasicGroup $basicGroup)
    {
        $menuPath = config_path('dynamic-menu.php');
        $menuConfig = require $menuPath;

        // حفظ معلومات الوحدات الفرعية قبل الحذف للاستعادة لاحقاً
        $childrenBackup = [];
        foreach ($menuConfig['menu_items'] as $key => $item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                $childrenBackup = $item['children'] ?? [];
                break;
            }
        }

        // حفظ النسخة الاحتياطية
        if (!empty($childrenBackup)) {
            self::saveChildrenBackup($basicGroup, $childrenBackup);
        }

        // إزالة المجموعة من القائمة
        foreach ($menuConfig['menu_items'] as $key => $item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                unset($menuConfig['menu_items'][$key]);
                $menuConfig['menu_items'] = array_values($menuConfig['menu_items']);
                break;
            }
        }

        // حذف الصلاحية المرتبطة بالمجموعة من قاعدة البيانات
        self::deletePermissionForGroup($basicGroup);

        self::saveMenuConfig($menuPath, $menuConfig);
    }

    /**
     * حفظ إعدادات القائمة في الملف
     */
    private static function saveMenuConfig(string $menuPath, array $menuConfig)
    {
        try {
            $configContent = "<?php\n\nreturn " . var_export($menuConfig, true) . ";\n";

            // التأكد من إمكانية الكتابة في الملف
            if (!is_writable(dirname($menuPath))) {
                throw new \Exception("لا يمكن الكتابة في مجلد التكوين: " . dirname($menuPath));
            }

            // حفظ الملف
            $result = file_put_contents($menuPath, $configContent);

            if ($result === false) {
                throw new \Exception("فشل في حفظ ملف القائمة الديناميكية");
            }

            // مسح كاش التكوين لضمان إعادة التحميل
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($menuPath, true);
            }

            Log::info("تم حفظ ملف القائمة الديناميكية بنجاح - حجم البيانات: {$result} بايت");

        } catch (\Exception $e) {
            Log::error("خطأ في حفظ ملف القائمة الديناميكية: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * إنشاء صلاحية جديدة للمجموعة في قاعدة البيانات
     */
    private static function createPermissionForGroup(BasicGroup $basicGroup)
    {
        $permissionName = $basicGroup->name_en;
        $existingPermission = Permission::where('name', $permissionName)->first();

        if (!$existingPermission) {
            try {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'explain_name' => $basicGroup->name_ar,
                ]);
            } catch (\Exception $e) {
                Log::warning('فشل في إنشاء الصلاحية: ' . $e->getMessage());
            }
        } else {
            try {
                $existingPermission->update([
                    'explain_name' => $basicGroup->name_ar
                ]);
            } catch (\Exception $e) {
                Log::warning('فشل في تحديث الصلاحية: ' . $e->getMessage());
            }
        }
    }

    /**
     * تحديث صلاحية المجموعة في قاعدة البيانات
     */
    private static function updatePermissionForGroup(BasicGroup $basicGroup)
    {
        $currentPermission = Permission::whereIn('name', [
            $basicGroup->getOriginal('name_en'),
            $basicGroup->name_en
        ])->first();

        if ($currentPermission) {
            $currentPermission->update([
                'name' => $basicGroup->name_en,
                'explain_name' => $basicGroup->name_ar
            ]);
        } else {
            self::createPermissionForGroup($basicGroup);
        }
    }

    /**
     * حذف صلاحية المجموعة من قاعدة البيانات
     */
    private static function deletePermissionForGroup(BasicGroup $basicGroup)
    {
        try {
            $permissionName = $basicGroup->name_en;
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                // حذف الصلاحية من جميع الأدوار والمستخدمين أولاً
                $permission->roles()->detach();
                $permission->users()->detach();

                // ثم حذف الصلاحية نفسها
                $permission->delete();

                Log::info("تم حذف الصلاحية: {$permissionName} للمجموعة: {$basicGroup->name_ar}");
            }
        } catch (\Exception $e) {
            Log::error('فشل في حذف الصلاحية: ' . $e->getMessage());
        }
    }

    /**
     * تحديث جميع المجموعات في القائمة من قاعدة البيانات
     */
    public static function syncAllBasicGroups()
    {
        $menuPath = config_path('dynamic-menu.php');
        $menuConfig = require $menuPath;

        // احذف جميع المجموعات الأساسية الحالية من القائمة
        $menuConfig['menu_items'] = array_filter($menuConfig['menu_items'], function ($item) {
            return !isset($item['basic_group_id']);
        });

        // أعد إضافة جميع المجموعات الأساسية
        $basicGroups = BasicGroup::where('status', true)->orderBy('sort_order')->get();
        foreach ($basicGroups as $basicGroup) {
            self::addGroupToMenu($menuConfig, $basicGroup);
            self::createPermissionForGroup($basicGroup);
        }

        self::saveMenuConfig($menuPath, $menuConfig);
    }

    /**
     * استعادة الوحدات الفرعية للمجموعة الأساسية عند الاستعادة
     */
    private static function restoreSubModulesForGroup(&$menuConfig, BasicGroup $basicGroup)
    {
        // محاولة استرداد النسخة الاحتياطية أولاً
        $backupChildren = self::restoreChildrenBackup($basicGroup);

        if (!empty($backupChildren)) {
            // التحقق من أن الوحدات ما زالت موجودة في النظام
            $validChildren = [];
            foreach ($backupChildren as $child) {
                if (self::moduleFilesExist($child['permission'])) {
                    $validChildren[] = $child;
                }
            }

            if (!empty($validChildren)) {
                // إضافة الوحدات المستردة من النسخة الاحتياطية
                foreach ($menuConfig['menu_items'] as $key => &$item) {
                    if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                        $item['children'] = $validChildren;

                        // تحديث active_routes
                        $activeRoutes = [$basicGroup->name_en];
                        foreach ($validChildren as $child) {
                            if (isset($child['active_routes'])) {
                                $activeRoutes = array_merge($activeRoutes, $child['active_routes']);
                            }
                        }
                        $item['active_routes'] = array_unique($activeRoutes);

                        break;
                    }
                }

                Log::info("تم استعادة " . count($validChildren) . " وحدة من النسخة الاحتياطية للمجموعة: {$basicGroup->name_ar}");
                return;
            }
        }

        // إذا لم تنجح النسخة الاحتياطية، استخدم البحث التلقائي
        $existingModules = self::findExistingModulesForGroupFromSystem($basicGroup);

        if (!empty($existingModules)) {
            // العثور على المجموعة في القائمة وإضافة الوحدات لها
            foreach ($menuConfig['menu_items'] as $key => &$item) {
                if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                    // إضافة الوحدات الموجودة
                    foreach ($existingModules as $module) {
                        // التحقق من عدم وجود الوحدة مسبقاً
                        $moduleExists = false;
                        foreach ($item['children'] as $child) {
                            if ($child['permission'] === $module['permission']) {
                                $moduleExists = true;
                                break;
                            }
                        }

                        if (!$moduleExists) {
                            $item['children'][] = [
                                'type' => 'item',
                                'permission' => $module['permission'],
                                'title' => $module['title'],
                                'url' => $module['url'],
                                'active_routes' => [$module['route']]
                            ];

                            // إضافة للـ active_routes
                            if (!in_array($module['route'], $item['active_routes'])) {
                                $item['active_routes'][] = $module['route'];
                            }
                        }
                    }
                    break;
                }
            }

            Log::info("تم استعادة " . count($existingModules) . " وحدة فرعية بالبحث التلقائي للمجموعة: {$basicGroup->name_ar}");
        }
    }

    /**
     * العثور على الوحدات الموجودة في النظام المرتبطة بالمجموعة الأساسية
     * من خلال البحث في ملفات Controllers و Routes و modules_config
     */
    private static function findExistingModulesForGroupFromSystem($basicGroup): array
    {
        $modules = [];

        // تحويل BasicGroup إلى string إذا لزم الأمر
        $groupPermission = is_string($basicGroup) ? $basicGroup : $basicGroup->permission;

        // البحث في modules_config أولاً
        $configModules = self::scanModulesConfigForGroup($groupPermission);
        $modules = array_merge($modules, $configModules);

        // أسماء الوحدات المعروفة للمجموعات المختلفة
        $knownModulesByGroup = [
            'Projects' => ['Employees', 'WorkDepartments', 'JobTitles'], // مثلاً
            'Settings' => ['UserManagement', 'Permissions', 'BackupSettings'],
            // يمكن إضافة المزيد حسب النظام
        ];

        // إذا كانت هناك وحدات معروفة للمجموعة
        $groupName = is_string($basicGroup) ? $basicGroup : $basicGroup->name_en;
        if (isset($knownModulesByGroup[$groupName])) {
            foreach ($knownModulesByGroup[$groupName] as $moduleName) {
                // تجنب التكرار
                $exists = false;
                foreach ($modules as $existingModule) {
                    if ($existingModule['permission'] === $moduleName) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists && self::moduleFilesExist($moduleName)) {
                    $modules[] = [
                        'permission' => $moduleName,
                        'title' => self::getModuleArabicName($moduleName),
                        'url' => '/' . strtolower($moduleName),
                        'route' => strtolower($moduleName)
                    ];
                }
            }
        } else {
            // البحث التلقائي في مجلد Controllers
            $controllerModules = self::scanControllersForModules($basicGroup);

            // دمج النتائج مع تجنب التكرار
            foreach ($controllerModules as $controllerModule) {
                $exists = false;
                foreach ($modules as $existingModule) {
                    if ($existingModule['permission'] === $controllerModule['permission']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $modules[] = $controllerModule;
                }
            }
        }

        return $modules;
    }

    /**
     * البحث في مجلد modules_config عن وحدات مُعرَّفة
     */
    private static function scanModulesConfigForGroup($groupPermission): array
    {
        $modules = [];
        $configPath = storage_path('app/modules_config');

        if (!is_dir($configPath)) {
            return $modules;
        }

        $configFiles = glob($configPath . '/*_fields.json');

        foreach ($configFiles as $file) {
            $fileName = basename($file, '_fields.json');

            // قراءة ملف التكوين
            try {
                $content = file_get_contents($file);
                $config = json_decode($content, true);

                if (isset($config['module_name'])) {
                    $moduleName = $config['module_name'];

                    // التحقق إذا كانت هذه الوحدة تنتمي لهذه المجموعة
                    $moduleParentGroup = $config['parent_group'] ?? null;

                    // إذا كان parent_group محدد، يجب أن يطابق المجموعة المطلوبة
                    if ($moduleParentGroup && $moduleParentGroup !== $groupPermission) {
                        continue; // تجاهل هذه الوحدة إذا لم تكن تنتمي للمجموعة
                    }

                    // إذا لم يكن parent_group محدد، أضف الوحدة لجميع المجموعات (سلوك قديم)
                    // أو يمكن تغيير هذا المنطق حسب الحاجة

                    $modules[] = [
                        'permission' => $moduleName,
                        'title' => self::getModuleArabicName($moduleName),
                        'url' => '/' . strtolower($moduleName),
                        'route' => strtolower($moduleName)
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("خطأ في قراءة ملف تكوين الوحدة: {$file}");
            }
        }

        return $modules;
    }

    /**
     * البحث في مجلد Controllers عن وحدات قد تكون مرتبطة بالمجموعة
     */
    private static function scanControllersForModules(BasicGroup $basicGroup): array
    {
        $modules = [];
        $controllersPath = app_path('Http/Controllers');

        if (!is_dir($controllersPath)) {
            return $modules;
        }

        $directories = glob($controllersPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $moduleName = basename($dir);

            // تجاهل المجلدات العامة
            if (in_array($moduleName, ['Auth', 'Management', 'Api'])) {
                continue;
            }

            // التحقق من وجود ملفات الوحدة
            if (self::moduleFilesExist($moduleName)) {
                $modules[] = [
                    'permission' => $moduleName,
                    'title' => self::getModuleArabicName($moduleName),
                    'url' => '/' . strtolower($moduleName),
                    'route' => strtolower($moduleName)
                ];
            }
        }

        return $modules;
    }

    /**
     * محاولة الحصول على الاسم العربي للوحدة
     */
    private static function getModuleArabicName(string $moduleName): string
    {
        // قاموس أسماء الوحدات الشائعة
        $moduleNames = [
            'Employees' => 'الموظفين',
            'WorkDepartments' => 'أقسام العمل',
            'JobTitles' => 'المناصب الوظيفية',
            'Projects' => 'المشاريع',
            'Tasks' => 'المهام',
            'Reports' => 'التقارير',
            'Settings' => 'الإعدادات',
            'UserManagement' => 'إدارة المستخدمين',
            'Permissions' => 'الصلاحيات',
            'BackupSettings' => 'إعدادات النسخ الاحتياطي',
        ];

        return $moduleNames[$moduleName] ?? $moduleName;
    }

    /**
     * التحقق من وجود ملفات الوحدة في النظام
     */
    private static function moduleFilesExist(string $moduleName): bool
    {
        // التحقق من وجود ملف تكوين الوحدة في modules_config (هذا يعني أن الوحدة تم إنشاؤها من مدير الوحدات)
        $configPath = storage_path("app/modules_config/{$moduleName}_fields.json");
        if (file_exists($configPath)) {
            Log::info("تم العثور على تكوين الوحدة: {$moduleName}");
            return true;
        }

        // التحقق من وجود Controller
        $controllerPath = app_path("Http/Controllers/{$moduleName}/{$moduleName}Controller.php");

        // التحقق من وجود Livewire
        $livewirePath = app_path("Http/Livewire/{$moduleName}/{$moduleName}.php");

        // التحقق من وجود Route
        $routePath = base_path("routes/{$moduleName}.php");

        $filesExist = file_exists($controllerPath) && file_exists($livewirePath) && file_exists($routePath);

        if ($filesExist) {
            Log::info("تم العثور على ملفات الوحدة الكاملة: {$moduleName}");
        }

        return $filesExist;
    }

    /**
     * فحص وإعادة ربط جميع الوحدات المفقودة بالمجموعات الأساسية
     */
    public static function rescanAndRestoreAllMissingModules(): int
    {
        $menuPath = config_path('dynamic-menu.php');
        $menuConfig = require $menuPath;
        $restoredCount = 0;

        // الحصول على جميع المجموعات الأساسية المفعلة
        $basicGroups = BasicGroup::where('status', true)->get();

        foreach ($basicGroups as $basicGroup) {
            $restoredModules = self::rescanAndRestoreForSpecificGroup($menuConfig, $basicGroup);
            $restoredCount += $restoredModules;
        }

        // حفظ التغييرات
        if ($restoredCount > 0) {
            self::saveMenuConfig($menuPath, $menuConfig);
            Log::info("تم استعادة {$restoredCount} وحدة مفقودة إجمالياً");
        }

        return $restoredCount;
    }

    /**
     * فحص وإعادة ربط الوحدات المفقودة لمجموعة محددة
     */
    private static function rescanAndRestoreForSpecificGroup(&$menuConfig, BasicGroup $basicGroup): int
    {
        $restoredCount = 0;

        // البحث عن الوحدات الموجودة في النظام
        $existingModules = self::findExistingModulesForGroupFromSystem($basicGroup);

        // العثور على المجموعة في القائمة
        foreach ($menuConfig['menu_items'] as $key => &$item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                // الحصول على قائمة الوحدات الحالية في القائمة
                $currentModules = [];
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        $currentModules[] = $child['permission'];
                    }
                }

                // إضافة الوحدات المفقودة فقط
                foreach ($existingModules as $module) {
                    if (!in_array($module['permission'], $currentModules)) {
                        $item['children'][] = [
                            'type' => 'item',
                            'permission' => $module['permission'],
                            'title' => $module['title'],
                            'url' => $module['url'],
                            'active_routes' => [$module['route']]
                        ];

                        // إضافة للـ active_routes
                        if (!in_array($module['route'], $item['active_routes'])) {
                            $item['active_routes'][] = $module['route'];
                        }

                        $restoredCount++;
                        Log::info("تم استعادة الوحدة: {$module['title']} للمجموعة: {$basicGroup->name_ar}");
                    }
                }
                break;
            }
        }

        return $restoredCount;
    }

    /**
     * حفظ نسخة احتياطية من الوحدات الفرعية للمجموعة
     */
    private static function saveChildrenBackup(BasicGroup $basicGroup, array $children)
    {
        $backupPath = storage_path('app/menu_backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $backupFile = $backupPath . '/group_' . $basicGroup->id . '_children.json';
        file_put_contents($backupFile, json_encode($children, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::info("تم حفظ نسخة احتياطية للوحدات الفرعية للمجموعة: {$basicGroup->name_ar}");
    }

    /**
     * استرداد النسخة الاحتياطية للوحدات الفرعية
     */
    private static function restoreChildrenBackup(BasicGroup $basicGroup): array
    {
        $backupFile = storage_path('app/menu_backups/group_' . $basicGroup->id . '_children.json');

        if (file_exists($backupFile)) {
            $backup = json_decode(file_get_contents($backupFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info("تم استرداد النسخة الاحتياطية للوحدات الفرعية للمجموعة: {$basicGroup->name_ar}");
                return $backup;
            }
        }

        return [];
    }

    /**
     * تعطيل مجموعة في القائمة (إخفاء بدلاً من حذف)
     */
    private static function disableGroupInMenu(&$menuConfig, BasicGroup $basicGroup)
    {
        // حفظ معلومات الوحدات الفرعية قبل التعطيل
        foreach ($menuConfig['menu_items'] as $key => &$item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                $childrenBackup = $item['children'] ?? [];

                // حفظ النسخة الاحتياطية
                if (!empty($childrenBackup)) {
                    self::saveChildrenBackup($basicGroup, $childrenBackup);
                }

                // تعيين hidden بدلاً من حذف العنصر
                $item['hidden'] = true;
                $item['children'] = []; // إخفاء الوحدات الفرعية
                break;
            }
        }
    }

    /**
     * تفعيل مجموعة في القائمة (إظهار واستعادة الوحدات الفرعية)
     */
    private static function enableGroupInMenu(&$menuConfig, BasicGroup $basicGroup)
    {
        foreach ($menuConfig['menu_items'] as $key => &$item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] == $basicGroup->id) {
                // إظهار العنصر
                unset($item['hidden']);

                // استعادة الوحدات الفرعية من النسخة الاحتياطية
                $childrenBackup = self::restoreChildrenBackup($basicGroup);
                if (!empty($childrenBackup)) {
                    $item['children'] = $childrenBackup;
                }
                break;
            }
        }
    }

    /**
     * حفظ الصلاحيات وتعطيلها مؤقتاً
     */
    private static function backupAndDisablePermissions(BasicGroup $basicGroup)
    {
        try {
            $permissionName = $basicGroup->name_en;
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                // حفظ الأدوار والمستخدمين المرتبطين بالصلاحية
                $rolesBackup = $permission->roles()->pluck('id')->toArray();
                $usersBackup = $permission->users()->pluck('id')->toArray();

                // حفظ النسخة الاحتياطية
                $backupData = [
                    'permission_id' => $permission->id,
                    'roles' => $rolesBackup,
                    'users' => $usersBackup,
                    'disabled_at' => now()->toDateTimeString()
                ];

                $backupPath = storage_path('app/permission_backups');
                if (!is_dir($backupPath)) {
                    mkdir($backupPath, 0755, true);
                }

                $backupFile = $backupPath . '/group_' . $basicGroup->id . '_permissions.json';
                file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));

                // إزالة الصلاحية من الأدوار والمستخدمين (تعطيل مؤقت)
                $permission->roles()->detach();
                $permission->users()->detach();

                Log::info("تم تعطيل الصلاحيات للمجموعة: {$basicGroup->name_ar} مع حفظ النسخة الاحتياطية");
            }
        } catch (\Exception $e) {
            Log::error('فشل في تعطيل الصلاحيات: ' . $e->getMessage());
        }
    }

    /**
     * استعادة الصلاحيات من النسخة الاحتياطية
     */
    private static function restorePermissions(BasicGroup $basicGroup)
    {
        try {
            $backupFile = storage_path('app/permission_backups/group_' . $basicGroup->id . '_permissions.json');

            if (file_exists($backupFile)) {
                $backupData = json_decode(file_get_contents($backupFile), true);

                if ($backupData && json_last_error() === JSON_ERROR_NONE) {
                    $permission = Permission::find($backupData['permission_id']);

                    if ($permission) {
                        // استعادة ربط الصلاحية بالأدوار
                        if (!empty($backupData['roles'])) {
                            $permission->roles()->sync($backupData['roles']);
                        }

                        // استعادة ربط الصلاحية بالمستخدمين
                        if (!empty($backupData['users'])) {
                            $permission->users()->sync($backupData['users']);
                        }

                        // حذف ملف النسخة الاحتياطية بعد الاستعادة الناجحة
                        unlink($backupFile);

                        Log::info("تم استعادة الصلاحيات للمجموعة: {$basicGroup->name_ar}");
                    } else {
                        // إذا لم توجد الصلاحية، أنشئها مجدداً
                        self::createPermissionForGroup($basicGroup);
                        Log::warning("لم توجد الصلاحية، تم إنشاؤها مجدداً للمجموعة: {$basicGroup->name_ar}");
                    }
                }
            } else {
                // إذا لم يوجد ملف احتياطي، أنشئ الصلاحية
                self::createPermissionForGroup($basicGroup);
                Log::info("لم يوجد ملف احتياطي، تم إنشاء صلاحيات جديدة للمجموعة: {$basicGroup->name_ar}");
            }
        } catch (\Exception $e) {
            Log::error('فشل في استعادة الصلاحيات: ' . $e->getMessage());
        }
    }

    /**
     * تحديث المجموعة الأب للوحدة
     */
    public function updateParentGroup($moduleName, $newParentGroup)
    {
        try {
            $menuPath = config_path('dynamic-menu.php');
            $menuConfig = require $menuPath;

            // البحث عن الوحدة في جميع المجموعات وإزالتها من المجموعة الحالية
            $moduleFound = false;
            $moduleData = null;
            $modulePermission = null;

            foreach ($menuConfig['menu_items'] as $groupKey => &$group) {
                if ($group['type'] === 'group' && isset($group['children'])) {
                    foreach ($group['children'] as $childKey => $child) {
                        if (isset($child['permission']) &&
                            (strtolower($child['permission']) === strtolower($moduleName) ||
                             $child['permission'] === $moduleName)) {
                            // حفظ بيانات الوحدة
                            $moduleData = $child;
                            $modulePermission = $child['permission'];
                            $moduleFound = true;

                            // إزالة الوحدة من المجموعة الحالية
                            unset($group['children'][$childKey]);
                            $group['children'] = array_values($group['children']);

                            // تحديث active_routes للمجموعة القديمة - إزالة permission الوحدة
                            if (isset($group['active_routes'])) {
                                $group['active_routes'] = array_filter($group['active_routes'], function($route) use ($modulePermission) {
                                    return strtolower($route) !== strtolower($modulePermission);
                                });
                                $group['active_routes'] = array_values($group['active_routes']); // إعادة ترقيم المؤشرات
                            }
                            break 2;
                        }
                    }
                }
            }

            if (!$moduleFound || !$moduleData) {
                Log::warning("لم يتم العثور على الوحدة {$moduleName} في القائمة الديناميكية");
                return false;
            }

            // البحث عن المجموعة الجديدة وإضافة الوحدة إليها
            $targetGroupFound = false;
            foreach ($menuConfig['menu_items'] as $groupKey => &$group) {
                if ($group['type'] === 'group' &&
                    (strtolower($group['permission']) === strtolower($newParentGroup) ||
                     $group['permission'] === $newParentGroup)) {

                    // إضافة الوحدة للمجموعة الجديدة
                    if (!isset($group['children'])) {
                        $group['children'] = [];
                    }
                    $group['children'][] = $moduleData;

                    // تحديث active_routes للمجموعة الجديدة
                    if (!isset($group['active_routes'])) {
                        $group['active_routes'] = [$group['permission']];
                    }

                    // إضافة permission الوحدة إلى active_routes إذا لم يكن موجوداً
                    if (!in_array($modulePermission, $group['active_routes'])) {
                        $group['active_routes'][] = $modulePermission;
                    }

                    $targetGroupFound = true;
                    break;
                }
            }

            if (!$targetGroupFound) {
                Log::error("لم يتم العثور على المجموعة الهدف {$newParentGroup}");
                return false;
            }

            // تنظيف active_routes - إزالة أي routes لا تحتوي على children مطابقة
            $this->cleanupActiveRoutes($menuConfig);

            // حفظ التغييرات
            self::saveMenuConfig($menuPath, $menuConfig);

            Log::info("تم نقل الوحدة {$moduleName} من مجموعتها الحالية إلى المجموعة {$newParentGroup}");
            return true;

        } catch (\Exception $e) {
            Log::error("خطأ في تحديث المجموعة الأب: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تنظيف active_routes من المسارات التي لا تحتوي على children مطابقة
     */
    private function cleanupActiveRoutes(&$menuConfig)
    {
        foreach ($menuConfig['menu_items'] as &$group) {
            if ($group['type'] === 'group') {
                $validRoutes = [$group['permission']]; // احتفظ بـ permission المجموعة نفسها

                // أضف permissions من children
                if (isset($group['children'])) {
                    foreach ($group['children'] as $child) {
                        if (isset($child['permission'])) {
                            $validRoutes[] = $child['permission'];
                        }
                    }
                }

                // تحديث active_routes
                $group['active_routes'] = array_values(array_unique($validRoutes));
            }
        }
    }

    /**
     * إصلاح ملف القائمة الديناميكية الحالي
     */
    public function fixCurrentMenuFile()
    {
        try {
            $menuPath = config_path('dynamic-menu.php');
            $menuConfig = require $menuPath;

            // تنظيف active_routes
            $this->cleanupActiveRoutes($menuConfig);

            // حفظ التغييرات
            self::saveMenuConfig($menuPath, $menuConfig);

            Log::info("تم إصلاح ملف القائمة الديناميكية");
            return true;

        } catch (\Exception $e) {
            Log::error("خطأ في إصلاح ملف القائمة الديناميكية: " . $e->getMessage());
            return false;
        }
    }

    /**
     * إعادة بناء القائمة واستعادة الوحدات المفقودة
     */
    public static function rebuildMenu()
    {
        try {
            $menuPath = config_path('dynamic-menu.php');

            // قراءة ملف القائمة الحالي
            if (!file_exists($menuPath)) {
                Log::error("ملف القائمة غير موجود: {$menuPath}");
                return false;
            }

            $menuConfig = include $menuPath;

            if (!isset($menuConfig['menu_items'])) {
                Log::error("تكوين القائمة غير صحيح");
                return false;
            }

            // تحديث المجموعات الموجودة مع وحداتها
            foreach ($menuConfig['menu_items'] as $groupKey => &$group) {
                if ($group['type'] === 'group') {
                    $existingModules = self::findExistingModulesForGroupFromSystem($group['permission']);
                    $group['children'] = $existingModules;

                    // تحديث active_routes
                    $activeRoutes = [];
                    foreach ($existingModules as $module) {
                        if (isset($module['permission'])) {
                            $activeRoutes[] = $module['permission'];
                        }
                    }
                    $group['active_routes'] = $activeRoutes;
                }
            }

            // حفظ التغييرات
            self::saveMenuConfig($menuPath, $menuConfig);

            Log::info("تم إعادة بناء القائمة بنجاح واستعادة الوحدات المفقودة");
            return true;

        } catch (\Exception $e) {
            Log::error("خطأ في إعادة بناء القائمة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تنظيف القائمة من الوحدات الاختبارية والإبقاء على الوحدات الأساسية فقط
     */
    public static function cleanMenu()
    {
        try {
            $menuPath = config_path('dynamic-menu.php');

            if (!file_exists($menuPath)) {
                Log::error("ملف القائمة غير موجود: {$menuPath}");
                return false;
            }

            $menuConfig = include $menuPath;

            if (!isset($menuConfig['menu_items'])) {
                Log::error("تكوين القائمة غير صحيح");
                return false;
            }

            // الوحدات المسموحة لكل مجموعة
            $allowedModules = [
                'Projects' => ['Employees'], // فقط وحدة الموظفين في مجموعة المشاريع
                'Settings' => [] // لا توجد وحدات مخصصة في الإعدادات حالياً
            ];

            // تنظيف كل مجموعة
            foreach ($menuConfig['menu_items'] as $groupKey => &$group) {
                if ($group['type'] === 'group') {
                    $groupPermission = $group['permission'];

                    if (isset($allowedModules[$groupPermission])) {
                        $cleanedChildren = [];
                        $cleanedActiveRoutes = [];

                        // فلترة الوحدات المسموحة فقط
                        foreach ($group['children'] as $child) {
                            if (in_array($child['permission'], $allowedModules[$groupPermission])) {
                                $cleanedChildren[] = $child;
                                $cleanedActiveRoutes[] = $child['permission'];
                            }
                        }

                        $group['children'] = $cleanedChildren;
                        $group['active_routes'] = $cleanedActiveRoutes;
                    }
                }
            }

            // حفظ التغييرات
            self::saveMenuConfig($menuPath, $menuConfig);

            Log::info("تم تنظيف القائمة بنجاح والإبقاء على الوحدات الأساسية فقط");
            return true;

        } catch (\Exception $e) {
            Log::error("خطأ في تنظيف القائمة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * إصلاح نوع المجموعات في القائمة (تحويل item إلى group للمجموعات الأساسية)
     */
    public static function fixMenuGroupTypes()
    {
        try {
            $menuPath = config_path('dynamic-menu.php');

            if (!file_exists($menuPath)) {
                Log::error("ملف القائمة غير موجود: {$menuPath}");
                return false;
            }

            $menuConfig = include $menuPath;

            if (!isset($menuConfig['menu_items'])) {
                Log::error("تكوين القائمة غير صحيح");
                return false;
            }

            $fixedCount = 0;

            // فحص وإصلاح كل عنصر في القائمة
            foreach ($menuConfig['menu_items'] as $itemKey => &$item) {
                // إذا كان العنصر له basic_group_id، فهو مُنشأ من صفحة المجموعات الأساسية
                if (isset($item['basic_group_id'])) {
                    $basicGroup = BasicGroup::find($item['basic_group_id']);

                    if ($basicGroup) {
                        // أي مجموعة مُنشأة من صفحة المجموعات الأساسية يجب أن تكون group
                        $correctType = 'group';

                        // إذا كان النوع الحالي مختلف عن النوع الصحيح
                        if ($item['type'] !== $correctType) {
                            $oldType = $item['type'];
                            $item['type'] = $correctType;

                            // تحويل إلى group
                            if (isset($item['route'])) {
                                unset($item['route']);
                            }
                            if (!isset($item['children'])) {
                                $item['children'] = [];
                            }

                            $fixedCount++;
                            Log::info("تم إصلاح نوع المجموعة: {$item['title']} من {$oldType} إلى {$correctType}");
                        }
                    }
                }
            }            // حفظ التغييرات
            self::saveMenuConfig($menuPath, $menuConfig);

            Log::info("تم إصلاح {$fixedCount} مجموعة في القائمة");
            return true;

        } catch (\Exception $e) {
            Log::error("خطأ في إصلاح أنواع المجموعات: " . $e->getMessage());
            return false;
        }
    }

    /**
     * إضافة وحدة مُنشأة من مولد الوحدات إلى القائمة (كـ item وليس group)
     */
    public static function addModuleToMenu($moduleName, $arabicName, $parentGroup = null, $icon = 'mdi mdi-folder-outline')
    {
        try {
            $menuPath = config_path('dynamic-menu.php');
            $menuConfig = require $menuPath;

            // التحقق من عدم وجود الوحدة مسبقاً
            foreach ($menuConfig['menu_items'] as $item) {
                if (isset($item['permission']) && $item['permission'] === $moduleName) {
                    return; // الوحدة موجودة مسبقاً
                }
            }

            $newMenuItem = [
                'type' => 'item', // الوحدات من مولد الوحدات تكون item دائماً
                'permission' => $moduleName,
                'title' => $arabicName,
                'icon' => $icon,
                'route' => $moduleName,
                'active_routes' => [$moduleName]
            ];

            // إذا تم تحديد مجموعة أب، أضف الوحدة كطفل
            if ($parentGroup) {
                $addedToParent = false;
                foreach ($menuConfig['menu_items'] as &$item) {
                    if ($item['type'] === 'group' && $item['permission'] === $parentGroup) {
                        if (!isset($item['children'])) {
                            $item['children'] = [];
                        }

                        // تحويل item إلى child module (إزالة route وإضافة url)
                        unset($newMenuItem['route']);
                        $newMenuItem['url'] = '/' . strtolower($moduleName);
                        $newMenuItem['route'] = strtolower($moduleName);

                        $item['children'][] = $newMenuItem;

                        // تحديث active_routes للمجموعة الأب
                        if (!in_array($moduleName, $item['active_routes'])) {
                            $item['active_routes'][] = $moduleName;
                        }

                        $addedToParent = true;
                        break;
                    }
                }

                if (!$addedToParent) {
                    // إذا لم توجد المجموعة الأب، أضف الوحدة كعنصر منفصل
                    $menuConfig['menu_items'][] = $newMenuItem;
                }
            } else {
                // إضافة الوحدة كعنصر منفصل
                $menuConfig['menu_items'][] = $newMenuItem;
            }

            self::saveMenuConfig($menuPath, $menuConfig);
            Log::info("تم إضافة وحدة {$moduleName} إلى القائمة كعنصر منفصل");

            // مسح الكاش للتأكد من إعادة تحميل التكوين
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                Log::info("تم مسح كاش التكوين بعد إضافة الوحدة {$moduleName}");
            } catch (\Exception $cacheException) {
                Log::warning("تحذير: فشل في مسح كاش التكوين: " . $cacheException->getMessage());
            }

        } catch (\Exception $e) {
            Log::error("خطأ في إضافة الوحدة إلى القائمة: " . $e->getMessage());
            throw $e; // إعادة رمي الاستثناء للتعامل معه في الكود المستدعي
        }
    }
}
