<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\PermissionCleaner;

class CleanModulePermissions extends Command
{
    /**
     * اسم الأمر ووصفه
     *
     * @var string
     */
    protected $signature = 'module:clean-permissions
                            {module? : اسم الوحدة لحذف صلاحياتها}
                            {--all : حذف جميع الصلاحيات الفارغة}
                            {--report : عرض تقرير مفصل فقط بدون حذف}';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'تنظيف وحذف صلاحيات الوحدات مع تقارير مفصلة';

    /**
     * تنفيذ الأمر
     *
     * @return int
     */
    public function handle()
    {
        $moduleName = $this->argument('module');
        $cleanAll = $this->option('all');
        $reportOnly = $this->option('report');

        if ($reportOnly && $moduleName) {
            return $this->showModuleReport($moduleName);
        }

        if ($cleanAll) {
            return $this->cleanOrphanedPermissions();
        }

        if ($moduleName) {
            return $this->cleanModulePermissions($moduleName);
        }

        // عرض المساعدة إذا لم يتم تحديد أي خيار
        $this->showHelp();
        return 0;
    }

    /**
     * عرض تقرير مفصل لوحدة معينة
     */
    private function showModuleReport($moduleName)
    {
        $this->info("📊 تقرير صلاحيات الوحدة: {$moduleName}");
        $this->line('════════════════════════════════════');

        $report = PermissionCleaner::getModulePermissionsReport($moduleName);

        if ($report['total_permissions'] === 0) {
            $this->warn("⚠️  لا توجد صلاحيات للوحدة {$moduleName}");
            return 0;
        }

        $this->info("📈 إجمالي الصلاحيات: {$report['total_permissions']}");
        $this->newLine();

        $headers = ['الصلاحية', 'Guard', 'الأدوار', 'المستخدمين', 'تاريخ الإنشاء'];
        $rows = [];

        foreach ($report['permissions'] as $permission) {
            $rows[] = [
                $permission['name'],
                $permission['guard_name'],
                $permission['roles_count'] . ' (' . implode(', ', $permission['roles']) . ')',
                $permission['users_count'],
                $permission['created_at']->format('Y-m-d H:i')
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * حذف الصلاحيات الفارغة
     */
    private function cleanOrphanedPermissions()
    {
        $this->info("🧹 بدء تنظيف الصلاحيات الفارغة...");

        $results = PermissionCleaner::cleanOrphanedPermissions();

        if ($results['cleaned_permissions'] > 0) {
            $this->info("✅ تم حذف {$results['cleaned_permissions']} صلاحية فارغة");
        } else {
            $this->info("✅ لا توجد صلاحيات فارغة للحذف");
        }

        if (!empty($results['errors'])) {
            $this->error("❌ حدثت أخطاء:");
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }

        return 0;
    }

    /**
     * حذف صلاحيات وحدة معينة
     */
    private function cleanModulePermissions($moduleName)
    {
        $this->info("🗑️  بدء حذف صلاحيات الوحدة: {$moduleName}");

        // عرض التقرير أولاً
        $report = PermissionCleaner::getModulePermissionsReport($moduleName);

        if ($report['total_permissions'] === 0) {
            $this->warn("⚠️  لا توجد صلاحيات للوحدة {$moduleName}");
            return 0;
        }

        $this->warn("⚠️  سيتم حذف {$report['total_permissions']} صلاحية:");
        foreach ($report['permissions'] as $permission) {
            $this->line("   • {$permission['name']} (مرتبطة بـ {$permission['roles_count']} دور و {$permission['users_count']} مستخدم)");
        }

        // التحقق من وجود STDIN (للاستدعاء من Terminal) أم لا (للاستدعاء من الويب)
        if (php_sapi_name() === 'cli' && defined('STDIN')) {
            if (!$this->confirm('هل أنت متأكد من المتابعة؟')) {
                $this->info('تم الإلغاء');
                return 0;
            }
        } else {
            // تنفيذ مباشر من الويب بدون تأكيد
            $this->info('🔄 تنفيذ حذف الصلاحيات من واجهة الويب...');
        }

        // تنفيذ الحذف
        $results = PermissionCleaner::deleteModulePermissions($moduleName);

        if ($results['deleted_count'] > 0) {
            $this->info("✅ تم حذف {$results['deleted_count']} صلاحية للوحدة {$moduleName}");

            if (!empty($results['details'])) {
                $this->info("📋 تفاصيل الحذف:");
                foreach ($results['details'] as $detail) {
                    $this->line("   • {$detail['permission']} (أدوار: {$detail['roles_detached']}, مستخدمين: {$detail['users_detached']})");
                }
            }
        }

        if (!empty($results['errors'])) {
            $this->error("❌ حدثت أخطاء:");
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }

        return 0;
    }

    /**
     * عرض المساعدة
     */
    private function showHelp()
    {
        $this->info("🔧 أداة تنظيف صلاحيات الوحدات");
        $this->line('════════════════════════════════');
        $this->newLine();

        $this->info("الاستخدامات:");
        $this->line("php artisan module:clean-permissions MyModule          # حذف صلاحيات وحدة معينة");
        $this->line("php artisan module:clean-permissions MyModule --report # عرض تقرير الصلاحيات فقط");
        $this->line("php artisan module:clean-permissions --all             # حذف جميع الصلاحيات الفارغة");
        $this->newLine();

        $this->info("أمثلة:");
        $this->line("php artisan module:clean-permissions Categories");
        $this->line("php artisan module:clean-permissions Tests --report");
        $this->line("php artisan module:clean-permissions --all");
    }
}
