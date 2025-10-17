<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TableCleaner
{
    /**
     * حذف جداول الوحدة مع تقرير مفصل
     *
     * @param string $moduleName
     * @return array
     */
    public static function dropModuleTables($moduleName)
    {
        $results = [
            'tables_dropped' => 0,
            'tables_checked' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            // قائمة الجداول المحتملة للوحدة
            $possibleTables = self::getPossibleTableNames($moduleName);

            foreach ($possibleTables as $tableName) {
                $results['tables_checked']++;

                try {
                    if (Schema::hasTable($tableName)) {
                        // جمع معلومات الجدول قبل الحذف
                        $tableInfo = self::getTableInfo($tableName);

                        // حذف الجدول
                        Schema::dropIfExists($tableName);

                        $results['tables_dropped']++;
                        $results['details'][] = [
                            'table_name' => $tableName,
                            'columns_count' => $tableInfo['columns_count'],
                            'rows_count' => $tableInfo['rows_count'],
                            'size_mb' => $tableInfo['size_mb'],
                            'status' => 'deleted'
                        ];

                        Log::info("تم حذف جدول {$tableName} للوحدة {$moduleName}", $tableInfo);
                    } else {
                        $results['details'][] = [
                            'table_name' => $tableName,
                            'status' => 'not_found'
                        ];
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "خطأ في حذف الجدول {$tableName}: " . $e->getMessage();
                    $results['details'][] = [
                        'table_name' => $tableName,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "خطأ عام في حذف جداول الوحدة {$moduleName}: " . $e->getMessage();
            Log::error("خطأ في حذف جداول الوحدة {$moduleName}: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * حذف جداول متعددة الوحدات
     *
     * @param array $moduleNames
     * @return array
     */
    public static function dropMultipleModuleTables($moduleNames)
    {
        $totalResults = [
            'total_tables_dropped' => 0,
            'modules_processed' => 0,
            'errors' => [],
            'details' => []
        ];

        foreach ($moduleNames as $moduleName) {
            $moduleResults = self::dropModuleTables($moduleName);

            $totalResults['total_tables_dropped'] += $moduleResults['tables_dropped'];
            $totalResults['modules_processed']++;
            $totalResults['errors'] = array_merge($totalResults['errors'], $moduleResults['errors']);
            $totalResults['details'][$moduleName] = $moduleResults;
        }

        return $totalResults;
    }

    /**
     * عرض تقرير مفصل عن جداول وحدة معينة
     *
     * @param string $moduleName
     * @return array
     */
    public static function getModuleTablesReport($moduleName)
    {
        $possibleTables = self::getPossibleTableNames($moduleName);

        $report = [
            'module' => $moduleName,
            'total_tables_found' => 0,
            'tables' => []
        ];

        foreach ($possibleTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                $tableInfo = self::getTableInfo($tableName);
                $report['tables'][] = array_merge(['table_name' => $tableName], $tableInfo);
                $report['total_tables_found']++;
            }
        }

        return $report;
    }

    /**
     * الحصول على أسماء الجداول المحتملة للوحدة
     *
     * @param string $moduleName
     * @return array
     */
    private static function getPossibleTableNames($moduleName)
    {
        $possibleTables = [
            strtolower($moduleName),
            strtolower(\Illuminate\Support\Str::plural($moduleName)),
            strtolower(\Illuminate\Support\Str::singular($moduleName)),
            strtolower(\Illuminate\Support\Str::snake($moduleName)),
            strtolower(\Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($moduleName)))
        ];

        // إزالة التكرار
        return array_unique($possibleTables);
    }

    /**
     * الحصول على معلومات الجدول
     *
     * @param string $tableName
     * @return array
     */
    private static function getTableInfo($tableName)
    {
        try {
            $columns = Schema::getColumnListing($tableName);
            $rowsCount = DB::table($tableName)->count();

            // محاولة الحصول على حجم الجدول (MySQL)
            $sizeInfo = DB::select("
                SELECT
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?
            ", [$tableName]);

            $sizeMb = !empty($sizeInfo) ? $sizeInfo[0]->size_mb : 0;

            return [
                'columns_count' => count($columns),
                'columns' => $columns,
                'rows_count' => $rowsCount,
                'size_mb' => $sizeMb
            ];
        } catch (\Exception $e) {
            return [
                'columns_count' => 0,
                'columns' => [],
                'rows_count' => 0,
                'size_mb' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تنظيف الجداول الفارغة
     *
     * @return array
     */
    public static function cleanEmptyTables()
    {
        $results = [
            'cleaned_tables' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            // الحصول على جميع الجداول
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $tableColumn = "Tables_in_{$databaseName}";

            foreach ($tables as $table) {
                $tableName = $table->{$tableColumn};

                // تجاهل جداول النظام
                if (in_array($tableName, ['migrations', 'password_resets', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens', 'sessions', 'users', 'permissions', 'roles', 'role_has_permissions', 'model_has_permissions', 'model_has_roles'])) {
                    continue;
                }

                try {
                    $rowsCount = DB::table($tableName)->count();

                    if ($rowsCount === 0) {
                        // الجدول فارغ، يمكن اعتباره للحذف
                        $results['details'][] = [
                            'table_name' => $tableName,
                            'status' => 'empty',
                            'rows_count' => 0
                        ];
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "خطأ في فحص الجدول {$tableName}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "خطأ في تنظيف الجداول الفارغة: " . $e->getMessage();
        }

        return $results;
    }
}
