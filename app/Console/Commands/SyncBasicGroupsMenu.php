<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Management\BasicGroup;
use Spatie\Permission\Models\Permission;

class SyncBasicGroupsMenu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:sync-basic-groups
                          {--force : Force sync even if groups already exist in menu}
                          {--show : Show current menu structure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync basic groups with dynamic menu configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('show')) {
            $this->showCurrentMenu();
            return;
        }

        $this->info('🔄 بدء تحديث القائمة الديناميكية...');

        try {
            // عدد المجموعات المفعلة
            $activeGroups = BasicGroup::active()->count();
            $this->info("📊 عدد المجموعات الأساسية المفعلة: {$activeGroups}");

            if ($activeGroups === 0) {
                $this->warn('⚠️  لا توجد مجموعات أساسية مفعلة للإضافة');
                return;
            }

            // تزامن المجموعات
            $this->syncAllBasicGroups();

            $this->info('✅ تم تحديث القائمة الديناميكية بنجاح');
            $this->info('📋 تم إضافة جميع المجموعات الأساسية المفعلة للقائمة الرئيسية');

            // عرض النتائج
            $this->showSyncResults();

        } catch (\Exception $e) {
            $this->error('❌ خطأ في تحديث القائمة: ' . $e->getMessage());
            $this->error('📍 في السطر: ' . $e->getLine());
            return 1;
        }

        return 0;
    }

    /**
     * عرض هيكل القائمة الحالية
     */
    private function showCurrentMenu()
    {
        $this->info('📋 هيكل القائمة الديناميكية الحالية:');
        $this->line('----------------------------------------');

        $menuPath = config_path('dynamic-menu.php');
        if (!file_exists($menuPath)) {
            $this->error('❌ ملف القائمة الديناميكية غير موجود');
            return;
        }

        $menuConfig = require $menuPath;

        foreach ($menuConfig['menu_items'] as $index => $item) {
            $icon = $item['icon'] ?? 'mdi mdi-folder';
            $title = $item['title'] ?? 'بدون عنوان';
            $type = $item['type'] ?? 'item';
            $hasBasicGroupId = isset($item['basic_group_id']) ? '🔗 متصل' : '📄 ثابت';

            $this->line("#{$index} [{$type}] {$icon} {$title} ({$hasBasicGroupId})");

            if (isset($item['children']) && is_array($item['children'])) {
                foreach ($item['children'] as $child) {
                    $childIcon = $child['icon'] ?? 'mdi mdi-file';
                    $childTitle = $child['title'] ?? 'بدون عنوان';
                    $this->line("   └── {$childIcon} {$childTitle}");
                }
            }
        }
    }

    /**
     * عرض نتائج التزامن
     */
    private function showSyncResults()
    {
        $basicGroups = BasicGroup::active()->orderBy('sort_order')->get();

        $this->line('');
        $this->info('📊 المجموعات التي تم إضافتها للقائمة:');
        $this->line('----------------------------------------');

        foreach ($basicGroups as $group) {
            $this->line("✅ {$group->icon} {$group->name_ar} ({$group->name_en})");
        }

        $this->line('');
        $this->info('💡 يمكنك الآن رؤية هذه المجموعات في القائمة الجانبية للنظام');
        $this->info('🔄 لعرض هيكل القائمة الحالية استخدم: php artisan menu:sync-basic-groups --show');
    }

    /**
     * تزامن جميع المجموعات الأساسية مع القائمة الديناميكية
     */
    private function syncAllBasicGroups()
    {
        $menuPath = config_path('dynamic-menu.php');
        $menuConfig = require $menuPath;

        // الحصول على المجموعات الأساسية المفعلة
        $basicGroups = BasicGroup::active()->orderBy('sort_order')->get();

        foreach ($basicGroups as $group) {
            // إنشاء صلاحية للمجموعة إذا لم تكن موجودة
            $this->createPermissionForGroup($group);

            // إضافة المجموعة للقائمة
            $this->addGroupToMenu($menuConfig, $group);
        }

        // حفظ القائمة المحدثة
        $this->saveMenuConfig($menuPath, $menuConfig);
    }

    /**
     * إنشاء صلاحية للمجموعة الأساسية
     */
    private function createPermissionForGroup(BasicGroup $group)
    {
        $permissionName = $group->name_en; // استخدام اسم الإنجليزي كما هو

        // البحث عن الصلاحية
        $permission = Permission::where('name', $permissionName)->first();

        if (!$permission) {
            Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            $this->info("✅ تم إنشاء صلاحية: {$permissionName}");
        }
    }

    /**
     * إضافة مجموعة للقائمة الديناميكية
     */
    private function addGroupToMenu(array &$menuConfig, BasicGroup $group)
    {
        // التحقق من وجود المجموعة في القائمة
        $exists = false;
        foreach ($menuConfig['menu_items'] as $item) {
            if (isset($item['basic_group_id']) && $item['basic_group_id'] === $group->id) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $menuConfig['menu_items'][] = [
                'type' => 'item', // تغيير من group إلى item
                'basic_group_id' => $group->id,
                'permission' => $group->name_en,
                'title' => $group->name_ar, // الاسم العربي للعرض في القائمة
                'icon' => $group->icon,
                'route' => $group->name_en, // إضافة route للرابط المباشر
                'active_routes' => [$group->name_en]
            ];
        }
    }

    /**
     * حفظ إعدادات القائمة
     */
    private function saveMenuConfig(string $path, array $config)
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($path, $content);
    }
}
