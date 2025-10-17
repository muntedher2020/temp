<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModuleRestoreHelper
{
    /**
     * إصلاح الوحدات المختفية تلقائياً
     */
    public static function fixMissingModules()
    {
        try {
            $fixedModules = [];
            $webRoutesPath = base_path('routes/web.php');

            if (!File::exists($webRoutesPath)) {
                Log::error('ملف Routes غير موجود');
                return ['success' => false, 'message' => 'ملف web.php غير موجود'];
            }

            $routesContent = File::get($webRoutesPath);
            $menuItems = config('dynamic-menu.menu_items', []);

            // استخراج أسماء الوحدات من routes
            $moduleNamesFromRoutes = self::extractModuleNamesFromRoutes($routesContent);

            // استخراج أسماء الوحدات من القائمة الحالية
            $moduleNamesFromMenu = self::extractModuleNamesFromMenu($menuItems);

            // العثور على الوحدات المفقودة
            $missingModules = array_diff($moduleNamesFromRoutes, $moduleNamesFromMenu);

            if (empty($missingModules)) {
                return ['success' => true, 'message' => 'جميع الوحدات موجودة في القائمة', 'fixed' => []];
            }

            // إصلاح كل وحدة مفقودة
            foreach ($missingModules as $moduleName) {
                // التحقق من وجود ملفات الوحدة فعلياً
                if (self::moduleFilesExist($moduleName)) {
                    $targetGroup = self::findSuitableGroupForModule($moduleName);

                    if ($targetGroup) {
                        self::addModuleToGroup($moduleName, $targetGroup);
                        $fixedModules[] = $moduleName;
                        Log::info("تم إصلاح الوحدة المفقودة: {$moduleName}");
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'تم إصلاح ' . count($fixedModules) . ' وحدة',
                'fixed' => $fixedModules
            ];

        } catch (\Exception $e) {
            Log::error("خطأ في إصلاح الوحدات المفقودة: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }

    /**
     * استخراج أسماء الوحدات من ملف routes
     */
    private static function extractModuleNamesFromRoutes($routesContent)
    {
        $modules = [];

        // أنماط مختلفة لاستخراج أسماء الوحدات
        $patterns = [
            '/Route::GET\([\'"]([A-Z][a-zA-Z0-9]*)[\'"]/',
            '/Route::get\([\'"]([A-Z][a-zA-Z0-9]*)[\'"]/',
            '/->name\([\'"]([A-Z][a-zA-Z0-9]*)[\'"]/',
            '/Controllers\\\\([A-Z][a-zA-Z0-9]*)\\\\/',
            '/([A-Z][a-zA-Z0-9]*)Controller::class/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $routesContent, $matches)) {
                foreach ($matches[1] as $moduleName) {
                    // تجاهل أسماء الطرق الأساسية
                    if (!in_array($moduleName, ['Dashboard', 'Users', 'Permissions', 'Roles'])) {
                        $modules[] = $moduleName;
                    }
                }
            }
        }

        return array_unique($modules);
    }

    /**
     * استخراج أسماء الوحدات من القائمة الحالية
     */
    private static function extractModuleNamesFromMenu($menuItems)
    {
        $modules = [];

        foreach ($menuItems as $item) {
            if (isset($item['permission'])) {
                $modules[] = $item['permission'];
            }

            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (isset($child['permission'])) {
                        $modules[] = $child['permission'];
                    }
                }
            }
        }

        return array_unique($modules);
    }

    /**
     * فحص وجود ملفات الوحدة
     */
    private static function moduleFilesExist($moduleName)
    {
        $paths = [
            base_path("app/Http/Controllers/{$moduleName}"),
            base_path("app/Http/Livewire/{$moduleName}"),
            base_path("app/Models/{$moduleName}"),
            base_path("app/Models/" . Str::singular($moduleName) . ".php")
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * البحث عن مجموعة مناسبة لإضافة الوحدة إليها
     */
    private static function findSuitableGroupForModule($moduleName)
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
     * إضافة الوحدة إلى مجموعة محددة مع تنظيف شامل
     */
    private static function addModuleToGroup($moduleName, $groupName)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        if (!isset($config['menu_items'])) {
            throw new \Exception('هيكل ملف التكوين غير صحيح');
        }

        $updated = false;

        // أولاً: تنظيف أي مراجع قديمة للوحدة من جميع المجموعات
        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group') {
                // إزالة من العناصر الفرعية
                if (isset($item['children'])) {
                    $item['children'] = array_filter($item['children'], function($child) use ($moduleName) {
                        return !(isset($child['permission']) && $child['permission'] === $moduleName);
                    });
                    $item['children'] = array_values($item['children']);
                }

                // إزالة من active_routes
                if (isset($item['active_routes'])) {
                    $item['active_routes'] = array_values(array_filter($item['active_routes'], function($route) use ($moduleName) {
                        return $route !== $moduleName;
                    }));
                }
            }
        }

        // ثانياً: إضافة الوحدة إلى المجموعة المحددة
        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group' && $item['permission'] === $groupName) {

                // إضافة الوحدة كعنصر فرعي
                $newItem = [
                    'type' => 'item',
                    'permission' => $moduleName,
                    'title' => self::getModuleArabicName($moduleName),
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

        if ($updated) {
            // كتابة الملف المحدث
            $newConfigContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($configPath, $newConfigContent);

            // مسح كاش التكوين
            \Illuminate\Support\Facades\Artisan::call('config:clear');

            Log::info("تم إضافة الوحدة {$moduleName} إلى مجموعة {$groupName} مع تنظيف شامل");
        } else {
            throw new \Exception('فشل في إضافة الوحدة إلى المجموعة');
        }
    }    /**
     * الحصول على الاسم العربي للوحدة
     */
    private static function getModuleArabicName($moduleName)
    {
        // وحدات النظام المعروفة
        $systemModules = [
            'Tests10' => 'فحص10',
            'Tests' => 'الفحوصات',
            'Projects' => 'المشاريع',
            'Settings' => 'الإعدادات',
            'Dashboard' => 'لوحة التحكم',
            'Users' => 'المستخدمين',
            'Employees' => 'الموظفين',
            'Departments' => 'الأقسام'
        ];

        return $systemModules[$moduleName] ?? $moduleName;
    }

    /**
     * فحص ما إذا كانت الوحدة لها routes نشطة
     */
    public static function checkIfModuleHasActiveRoutes($moduleName)
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
            return false;
        }
    }
}
