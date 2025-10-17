<?php

namespace App\Http\Controllers\DataManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DataManagement\DataExport;
use App\Helpers\PermissionHelper;

class DataManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:data-management-access', ['only' => ['index']]);
        $this->middleware('permission:data-management-export', ['only' => ['export', 'downloadTemplate']]);
        $this->middleware('permission:data-management-import', ['only' => ['import', 'processImport']]);
    }

    /**
     * عرض صفحة إدارة البيانات الرئيسية
     */
    public function index()
    {
        $permissions = PermissionHelper::getPermissions('data-management');

        // الحصول على جميع الجداول في قاعدة البيانات
        $tables = $this->getAllDatabaseTables();

        return view('content.data-management.index', compact('permissions', 'tables'));
    }

    /**
     * عرض صفحة إدارة جدول محدد
     */
    public function manageTable($tableName)
    {
        // التحقق من وجود الجدول
        if (!Schema::hasTable($tableName)) {
            return redirect()->route('data-management.index')
                ->with('error', "الجدول '{$tableName}' غير موجود في قاعدة البيانات");
        }

        $permissions = PermissionHelper::getPermissions('data-management');

        // معلومات الجدول
        $tableInfo = $this->getTableInfo($tableName);

        return view('content.data-management.manage-table', compact(
            'permissions',
            'tableName',
            'tableInfo'
        ));
    }

    /**
     * تصدير البيانات
     */
    public function export(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'export_format' => 'required|in:xlsx,csv,pdf',
            'columns' => 'array',
            'conditions' => 'array',
            'limit' => 'nullable|integer|min:1|max:50000'
        ]);

        $tableName = $request->table_name;

        // التحقق من وجود الجدول
        if (!Schema::hasTable($tableName)) {
            return response()->json(['error' => 'الجدول غير موجود'], 404);
        }

        try {
            $fileName = $this->generateFileName($tableName, $request->export_format);

            return Excel::download(
                new DataExport(
                    $tableName,
                    $request->columns,
                    $request->conditions,
                    $request->limit
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء التصدير: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تحميل قالب فارغ
     */
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'format' => 'required|in:xlsx,csv'
        ]);

        $tableName = $request->table_name;

        if (!Schema::hasTable($tableName)) {
            return response()->json(['error' => 'الجدول غير موجود'], 404);
        }

        try {
            $fileName = "template_{$tableName}." . $request->format;

            return Excel::download(
                new DataExport($tableName, [], [], 0, true), // القالب فارغ
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء إنشاء القالب: ' . $e->getMessage()], 500);
        }
    }

    /**
     * استيراد البيانات
     */
    public function import(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'import_file' => 'required|file|mimes:xlsx,csv,xls|max:10240', // 10MB
            'import_mode' => 'required|in:insert,update,replace'
        ]);

        $tableName = $request->table_name;

        if (!Schema::hasTable($tableName)) {
            return response()->json(['error' => 'الجدول غير موجود'], 404);
        }

        try {
            // معالجة الاستيراد عبر Livewire Component
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف بنجاح، سيتم معالجته الآن...'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage()], 500);
        }
    }

    /**
     * الحصول على جميع الجداول في قاعدة البيانات
     */
    private function getAllDatabaseTables()
    {
        $tables = [];
        $tableNames = DB::select('SHOW TABLES');

        foreach ($tableNames as $table) {
            $tableName = array_values((array) $table)[0];

            // تجاهل الجداول النظامية
            if (!in_array($tableName, ['migrations', 'failed_jobs', 'personal_access_tokens', 'sessions'])) {
                $tables[] = [
                    'name' => $tableName,
                    'display_name' => $this->getTableDisplayName($tableName),
                    'row_count' => DB::table($tableName)->count(),
                    'columns_count' => count(Schema::getColumnListing($tableName)),
                    'size' => $this->getTableSize($tableName)
                ];
            }
        }

        return collect($tables)->sortBy('display_name');
    }

    /**
     * الحصول على معلومات جدول محدد
     */
    private function getTableInfo($tableName)
    {
        $columns = Schema::getColumnListing($tableName);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($tableName, $column);
            $columnDetails[] = [
                'name' => $column,
                'type' => $columnType,
                'nullable' => $this->isColumnNullable($tableName, $column),
                'default' => $this->getColumnDefault($tableName, $column)
            ];
        }

        return [
            'name' => $tableName,
            'display_name' => $this->getTableDisplayName($tableName),
            'columns' => $columnDetails,
            'row_count' => DB::table($tableName)->count(),
            'indexes' => $this->getTableIndexes($tableName),
            'foreign_keys' => $this->getTableForeignKeys($tableName)
        ];
    }

    /**
     * الحصول على اسم عرض الجدول
     */
    private function getTableDisplayName($tableName)
    {
        // تحويل أسماء الجداول الإنجليزية إلى عربية
        $translations = [
            'users' => 'المستخدمين',
            'basic_groups' => 'المجموعات الأساسية',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'trackings' => 'التتبع',
            'online_sessions' => 'الجلسات النشطة'
        ];

        return $translations[$tableName] ?? ucfirst(str_replace('_', ' ', $tableName));
    }

    /**
     * الحصول على حجم الجدول
     */
    private function getTableSize($tableName)
    {
        try {
            $result = DB::select("
                SELECT
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?
            ", [$tableName]);

            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * فحص إذا كان العمود يقبل القيم الفارغة
     */
    private function isColumnNullable($tableName, $columnName)
    {
        try {
            $result = DB::select("
                SELECT is_nullable
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?
            ", [$tableName, $columnName]);

            return isset($result[0]) && $result[0]->is_nullable === 'YES';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * الحصول على القيمة الافتراضية للعمود
     */
    private function getColumnDefault($tableName, $columnName)
    {
        try {
            $result = DB::select("
                SELECT column_default
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?
            ", [$tableName, $columnName]);

            return $result[0]->column_default ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * الحصول على فهارس الجدول
     */
    private function getTableIndexes($tableName)
    {
        try {
            return DB::select("SHOW INDEX FROM `{$tableName}`");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * الحصول على المفاتيح الخارجية للجدول
     */
    private function getTableForeignKeys($tableName)
    {
        try {
            return DB::select("
                SELECT
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * إنشاء اسم الملف للتصدير
     */
    private function generateFileName($tableName, $format)
    {
        $date = now()->format('Y-m-d_H-i-s');
        return "{$tableName}_export_{$date}.{$format}";
    }
}
