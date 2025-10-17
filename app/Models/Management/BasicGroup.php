<?php

namespace App\Models\Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BasicGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'icon',
        'description_ar',
        'description_en',
        'sort_order',
        'status',
        'permission',
        'active_routes',
        'route',
        'type',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ------------------------ Scopes ------------------------ */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name_ar', 'asc');
    }

    /* ------------------------ Accessors ------------------------ */
    public function getLocalizedNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getLocalizedDescriptionAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function getStatusTextAttribute()
    {
        return $this->status ? 'مفعل' : 'غير مفعل';
    }

    public function getStatusClassAttribute()
    {
        return $this->status ? 'text-success' : 'text-danger';
    }

    /* ------------------------ Helper Methods ------------------------ */
    public function getIconPreview()
    {
        return '<i class="' . $this->icon . ' fs-2x"></i>';
    }

    /* ------------------------ Static Methods ------------------------ */
    public static function getNextSortOrder()
    {
        $maxSortOrder = static::max('sort_order');
        return $maxSortOrder ? $maxSortOrder + 1 : 0;
    }

    public static function getSuggestedSortOrder()
    {
        // البحث عن أول رقم متاح
        $usedSortOrders = static::pluck('sort_order')->sort()->toArray();

        for ($i = 0; $i <= count($usedSortOrders); $i++) {
            if (!in_array($i, $usedSortOrders)) {
                return $i;
            }
        }

        return self::getNextSortOrder();
    }
}
