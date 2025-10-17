<?php

namespace App\Models\DataManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DataTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'table_name',
        'description',
        'columns_config',
        'filters_config',
        'export_settings',
        'is_active',
        'created_by',
        'last_used_at'
    ];

    protected $casts = [
        'columns_config' => 'array',
        'filters_config' => 'array',
        'export_settings' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * العلاقة مع المستخدم الذي أنشأ القالب
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * إحصائيات استخدام القالب
     */
    public function usageLogs()
    {
        return $this->hasMany(DataTemplateUsage::class, 'template_id');
    }

    /**
     * Scope للقوالب النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للقوالب المرتبطة بجدول معين
     */
    public function scopeForTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * الحصول على الأعمدة المختارة للتصدير
     */
    public function getSelectedColumns()
    {
        if (!$this->columns_config || !isset($this->columns_config['selected'])) {
            return Schema::getColumnListing($this->table_name);
        }

        return $this->columns_config['selected'];
    }

    /**
     * الحصول على شروط التصفية
     */
    public function getFilters()
    {
        return $this->filters_config ?? [];
    }

    /**
     * تحديث وقت آخر استخدام
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * تسجيل استخدام القالب
     */
    public function logUsage($userId, $action, $recordsCount = 0, $filePath = null)
    {
        return $this->usageLogs()->create([
            'user_id' => $userId,
            'action' => $action, // export, import, template_download
            'records_count' => $recordsCount,
            'file_path' => $filePath,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * التحقق من صحة تكوين الأعمدة
     */
    public function validateColumnsConfig()
    {
        if (!Schema::hasTable($this->table_name)) {
            return false;
        }

        $availableColumns = Schema::getColumnListing($this->table_name);
        $selectedColumns = $this->getSelectedColumns();

        // التحقق من أن جميع الأعمدة المختارة موجودة في الجدول
        foreach ($selectedColumns as $column) {
            if (!in_array($column, $availableColumns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * إنشاء قالب افتراضي لجدول
     */
    public static function createDefaultTemplate($tableName, $userId)
    {
        if (!Schema::hasTable($tableName)) {
            throw new \Exception("الجدول {$tableName} غير موجود");
        }

        $columns = Schema::getColumnListing($tableName);

        return self::create([
            'name' => "قالب افتراضي - " . self::getTableDisplayName($tableName),
            'table_name' => $tableName,
            'description' => "قالب افتراضي يتضمن جميع أعمدة جدول {$tableName}",
            'columns_config' => [
                'selected' => $columns,
                'headers' => self::generateArabicHeaders($columns),
                'order' => $columns
            ],
            'filters_config' => [],
            'export_settings' => [
                'format' => 'xlsx',
                'include_headers' => true,
                'rtl_support' => true,
                'font_family' => 'Arial',
                'page_orientation' => 'landscape'
            ],
            'is_active' => true,
            'created_by' => $userId
        ]);
    }

    /**
     * إنشاء عناوين عربية للأعمدة
     */
    private static function generateArabicHeaders($columns)
    {
        $translations = [
            'id' => 'المعرف',
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'address' => 'العنوان',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'deleted_at' => 'تاريخ الحذف',
            'description' => 'الوصف',
            'title' => 'العنوان',
            'content' => 'المحتوى',
            'category' => 'الفئة',
            'price' => 'السعر',
            'quantity' => 'الكمية',
            'date' => 'التاريخ',
            'time' => 'الوقت'
        ];

        $headers = [];
        foreach ($columns as $column) {
            $headers[$column] = $translations[$column] ?? ucfirst(str_replace('_', ' ', $column));
        }

        return $headers;
    }

    /**
     * الحصول على اسم عرض الجدول
     */
    private static function getTableDisplayName($tableName)
    {
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
}


/**
 * Model لتتبع استخدام القوالب
 */
class DataTemplateUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'user_id',
        'action',
        'records_count',
        'file_path',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'records_count' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * العلاقة مع القالب
     */
    public function template()
    {
        return $this->belongsTo(DataTemplate::class, 'template_id');
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Scope للعمليات من نوع معين
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope للعمليات في فترة زمنية محددة
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
