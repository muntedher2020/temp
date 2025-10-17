<?php

namespace App\Models\ReportGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\System\ModuleField;

class ReportGenerator extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'module_name',
        'table_name',
        'selected_columns',
        'filter_columns',
        'filter_values',
        'chart_settings',
        'sort_column',
        'sort_direction',
        'is_public',
        'description',
        'created_by',
        'status'
    ];

    protected $casts = [
        'selected_columns' => 'array',
        'filter_columns' => 'array',
        'filter_values' => 'array',
        'chart_settings' => 'array',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم الذي أنشأ التقرير
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * احصل على جميع الوحدات المتاحة من جدول module_fields
     */
    public static function getAvailableModules()
    {
        // استخدام نموذج ModuleField للحصول على معلومات الوحدات
        $modulesInfo = ModuleField::getAllModulesInfo();

        return $modulesInfo->map(function($module) {
            return [
                'name' => $module->module_name,
                'arabic_name' => $module->module_arabic_name ?: self::getModuleArabicName($module->module_name),
                'table_name' => $module->table_name
            ];
        })->values()->toArray();
    }

    /**
     * احصل على الاسم العربي للوحدة
     */
    public static function getModuleArabicName($moduleName)
    {
        // استخدام نموذج ModuleField للحصول على الاسم العربي
        $arabicName = ModuleField::getModuleArabicName($moduleName);

        if ($arabicName) {
            return $arabicName;
        }

        // إذا لم يتم العثور على الاسم العربي في قاعدة البيانات، استخدم الاسم الإنجليزي مع تنسيق أفضل
        return ucfirst(str_replace('_', ' ', $moduleName));
    }

    /**
     * احصل على حقول وحدة معينة
     */
    public static function getModuleFields($moduleName)
    {
        // استخدام نموذج ModuleField للحصول على الحقول
        return ModuleField::getModuleFields($moduleName);
    }

    /**
     * احصل على اسم الجدول للوحدة
     */
    public static function getModuleTableName($moduleName)
    {
        // استخدام نموذج ModuleField للحصول على اسم الجدول
        $tableName = ModuleField::getModuleTableName($moduleName);

        if ($tableName) {
            return $tableName;
        }

        // استخدام الطريقة القديمة كبديل
        return \Illuminate\Support\Str::plural(strtolower($moduleName));
    }

    /**
     * فحص وجود الجدول في قاعدة البيانات
     */
    public static function checkTableExists($tableName)
    {
        return Schema::hasTable($tableName);
    }

    /**
     * احصل على أعمدة الجدول الفعلية
     */
    public static function getTableColumns($tableName)
    {
        if (!self::checkTableExists($tableName)) {
            return [];
        }

        return Schema::getColumnListing($tableName);
    }

    /**
     * احصل على نوع البيانات للعمود
     */
    public static function getColumnType($tableName, $columnName)
    {
        if (!self::checkTableExists($tableName)) {
            return null;
        }

        $type = DB::select("SHOW COLUMNS FROM {$tableName} WHERE Field = ?", [$columnName]);
        return $type ? $type[0]->Type : null;
    }

    /**
     * فحص ما إذا كان العمود رقمي
     */
    public static function isNumericColumn($tableName, $columnName)
    {
        $type = self::getColumnType($tableName, $columnName);
        if (!$type) return false;

        $numericTypes = ['int', 'integer', 'bigint', 'decimal', 'float', 'double', 'real'];

        foreach ($numericTypes as $numericType) {
            if (strpos(strtolower($type), $numericType) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * احصل على الحقول الرقمية فقط
     */
    public static function getNumericColumns($tableName)
    {
        $columns = self::getTableColumns($tableName);
        $numericColumns = [];

        foreach ($columns as $column) {
            if (self::isNumericColumn($tableName, $column)) {
                $numericColumns[] = $column;
            }
        }

        return $numericColumns;
    }

    /**
     * احصل على البيانات للتقرير
     */
    public function generateReportData()
    {
        if (!$this->table_name || !self::checkTableExists($this->table_name)) {
            return [];
        }

        $query = DB::table($this->table_name);

        // تطبيق الفلاتر
        if ($this->filter_values && is_array($this->filter_values)) {
            foreach ($this->filter_values as $column => $value) {
                if (!empty($value)) {
                    if (is_array($value)) {
                        $query->whereIn($column, $value);
                    } else {
                        // فحص نوع الفلتر
                        if (strpos($value, '%') !== false) {
                            $query->where($column, 'LIKE', $value);
                        } elseif (strpos($value, '>=') === 0) {
                            $query->where($column, '>=', substr($value, 2));
                        } elseif (strpos($value, '<=') === 0) {
                            $query->where($column, '<=', substr($value, 2));
                        } elseif (strpos($value, '>') === 0) {
                            $query->where($column, '>', substr($value, 1));
                        } elseif (strpos($value, '<') === 0) {
                            $query->where($column, '<', substr($value, 1));
                        } else {
                            $query->where($column, $value);
                        }
                    }
                }
            }
        }

        // تحديد الأعمدة المطلوبة
        if ($this->selected_columns && is_array($this->selected_columns)) {
            $query->select($this->selected_columns);
        }

        // ترتيب النتائج
        if ($this->sort_column) {
            $query->orderBy($this->sort_column, $this->sort_direction ?? 'asc');
        }

        return $query->get()->toArray();
    }

    /**
     * احصل على بيانات المخططات
     */
    public function getChartData()
    {
        if (!$this->chart_settings || !is_array($this->chart_settings)) {
            return [];
        }

        $chartData = [];

        foreach ($this->chart_settings as $chartConfig) {
            if (!isset($chartConfig['column']) || !isset($chartConfig['type'])) {
                continue;
            }

            $column = $chartConfig['column'];
            $chartType = $chartConfig['type'];

            // التأكد من أن العمود رقمي
            if (!self::isNumericColumn($this->table_name, $column)) {
                continue;
            }

            $query = DB::table($this->table_name);

            // تطبيق نفس الفلاتر
            if ($this->filter_values && is_array($this->filter_values)) {
                foreach ($this->filter_values as $filterColumn => $value) {
                    if (!empty($value) && $filterColumn !== $column) {
                        if (is_array($value)) {
                            $query->whereIn($filterColumn, $value);
                        } else {
                            $query->where($filterColumn, $value);
                        }
                    }
                }
            }

            switch ($chartType) {
                case 'sum':
                    $result = $query->sum($column);
                    $chartData[] = [
                        'type' => 'number',
                        'title' => 'مجموع ' . $column,
                        'value' => $result
                    ];
                    break;

                case 'avg':
                    $result = $query->avg($column);
                    $chartData[] = [
                        'type' => 'number',
                        'title' => 'متوسط ' . $column,
                        'value' => round($result, 2)
                    ];
                    break;

                case 'count':
                    $result = $query->count();
                    $chartData[] = [
                        'type' => 'number',
                        'title' => 'عدد السجلات',
                        'value' => $result
                    ];
                    break;

                case 'group':
                    $groupBy = $chartConfig['group_by'] ?? 'created_at';
                    $results = $query->select(
                        DB::raw("DATE({$groupBy}) as date"),
                        DB::raw("SUM({$column}) as total")
                    )
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                    $chartData[] = [
                        'type' => 'line',
                        'title' => 'مجموع ' . $column . ' حسب التاريخ',
                        'labels' => $results->pluck('date')->toArray(),
                        'data' => $results->pluck('total')->toArray()
                    ];
                    break;
            }
        }

        return $chartData;
    }
}
