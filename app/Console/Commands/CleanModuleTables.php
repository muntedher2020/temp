<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\TableCleaner;

class CleanModuleTables extends Command
{
    /**
     * اسم الأمر ووصفه
     *
     * @var string
     */
    protected $signature = 'module:clean-tables
                            {module? : اسم الوحدة لحذف جداولها}
                            {--empty : عرض الجداول الفارغة}
                            {--report : عرض تقرير مفصل فقط بدون حذف}';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'تنظيف وحذف جداول الوحدات مع تقارير مفصلة';

    /**
     * تنفيذ الأمر
     *
     * @return int
     */
    public function handle()
    {
        $moduleName = $this->argument('module');
        $showEmpty = $this->option('empty');
        $reportOnly = $this->option('report');

        if ($reportOnly && $moduleName) {
            return $this->showModuleTablesReport($moduleName);
        }

        if ($showEmpty) {
            return $this->showEmptyTables();
        }

        if ($moduleName) {
            return $this->cleanModuleTables($moduleName);
        }

        // عرض المساعدة إذا لم يتم تحديد أي خيار
        $this->showHelp();
        return 0;
    }

    /**
     * عرض تقرير مفصل لجداول وحدة معينة
     */
    private function showModuleTablesReport($moduleName)
    {
        $this->info("📊 تقرير جداول الوحدة: {$moduleName}");
        $this->line('════════════════════════════════════');

        $report = TableCleaner::getModuleTablesReport($moduleName);

        if ($report['total_tables_found'] === 0) {
            $this->warn("⚠️  لا توجد جداول للوحدة {$moduleName}");
            return 0;
        }

        $this->info("📈 إجمالي الجداول الموجودة: {$report['total_tables_found']}");
        $this->newLine();

        $headers = ['اسم الجدول', 'الأعمدة', 'الصفوف', 'الحجم (MB)', 'الحالة'];
        $rows = [];

        foreach ($report['tables'] as $table) {
            $rows[] = [
                $table['table_name'],
                $table['columns_count'],
                number_format($table['rows_count']),
                $table['size_mb'],
                '✅ موجود'
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * عرض الجداول الفارغة
     */
    private function showEmptyTables()
    {
        $this->info("📋 البحث عن الجداول الفارغة...");

        $results = TableCleaner::cleanEmptyTables();

        $emptyTables = array_filter($results['details'], function($table) {
            return $table['status'] === 'empty';
        });

        if (empty($emptyTables)) {
            $this->info("✅ لا توجد جداول فارغة");
            return 0;
        }

        $this->warn("⚠️  الجداول الفارغة الموجودة:");
        $headers = ['اسم الجدول', 'عدد الصفوف', 'الحالة'];
        $rows = [];

        foreach ($emptyTables as $table) {
            $rows[] = [
                $table['table_name'],
                $table['rows_count'],
                '🗂️ فارغ'
            ];
        }

        $this->table($headers, $rows);

        if (!empty($results['errors'])) {
            $this->error("❌ حدثت أخطاء:");
            foreach ($results['errors'] as $error) {
                $this->line("   • {$error}");
            }
        }

        return 0;
    }

    /**
     * حذف جداول وحدة معينة
     */
    private function cleanModuleTables($moduleName)
    {
        $this->info("🗑️  بدء حذف جداول الوحدة: {$moduleName}");

        // عرض التقرير أولاً
        $report = TableCleaner::getModuleTablesReport($moduleName);

        if ($report['total_tables_found'] === 0) {
            $this->warn("⚠️  لا توجد جداول للوحدة {$moduleName}");
            return 0;
        }

        $this->warn("⚠️  سيتم حذف {$report['total_tables_found']} جدول:");
        foreach ($report['tables'] as $table) {
            $this->line("   • {$table['table_name']} ({$table['columns_count']} عمود، {$table['rows_count']} صف، {$table['size_mb']} MB)");
        }

        // التحقق من وجود STDIN (للاستدعاء من Terminal) أم لا (للاستدعاء من الويب)
        if (php_sapi_name() === 'cli' && defined('STDIN')) {
            if (!$this->confirm('هل أنت متأكد من المتابعة؟')) {
                $this->info('تم الإلغاء');
                return 0;
            }
        } else {
            // تنفيذ مباشر من الويب بدون تأكيد
            $this->info('🔄 تنفيذ الحذف من واجهة الويب...');
        }

        // تنفيذ الحذف
        $results = TableCleaner::dropModuleTables($moduleName);

        if ($results['tables_dropped'] > 0) {
            $this->info("✅ تم حذف {$results['tables_dropped']} جدول للوحدة {$moduleName}");

            if (!empty($results['details'])) {
                $this->info("📋 تفاصيل الحذف:");
                foreach ($results['details'] as $detail) {
                    if ($detail['status'] === 'deleted') {
                        $this->line("   • {$detail['table_name']} (أعمدة: {$detail['columns_count']}, صفوف: {$detail['rows_count']}, حجم: {$detail['size_mb']} MB)");
                    }
                }
            }
        } else {
            $this->warn("⚠️  لم يتم حذف أي جداول");
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
        $this->info("🔧 أداة تنظيف جداول الوحدات");
        $this->line('════════════════════════════════');
        $this->newLine();

        $this->info("الاستخدامات:");
        $this->line("php artisan module:clean-tables MyModule          # حذف جداول وحدة معينة");
        $this->line("php artisan module:clean-tables MyModule --report # عرض تقرير الجداول فقط");
        $this->line("php artisan module:clean-tables --empty           # عرض الجداول الفارغة");
        $this->newLine();

        $this->info("أمثلة:");
        $this->line("php artisan module:clean-tables Products");
        $this->line("php artisan module:clean-tables Tests --report");
        $this->line("php artisan module:clean-tables --empty");
    }
}
