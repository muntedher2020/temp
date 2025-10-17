<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class BasicGroupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // صلاحيات المجموعات الأساسية - إدارة النظام فقط
        $permissions = [
            [
                'name' => 'BasicGroup-list',
                'guard_name' => 'web',
                'explain_name' => 'عرض قائمة المجموعات الأساسية'
            ],
            [
                'name' => 'BasicGroup-create',
                'guard_name' => 'web',
                'explain_name' => 'إنشاء مجموعة أساسية جديدة'
            ],
            [
                'name' => 'BasicGroup-edit',
                'guard_name' => 'web',
                'explain_name' => 'تعديل المجموعة الأساسية'
            ],
            [
                'name' => 'BasicGroup-delete',
                'guard_name' => 'web',
                'explain_name' => 'حذف المجموعة الأساسية'
            ],
            [
                'name' => 'BasicGroup-show',
                'guard_name' => 'web',
                'explain_name' => 'عرض تفاصيل المجموعة الأساسية'
            ],
            [
                'name' => 'BasicGroup-restore',
                'guard_name' => 'web',
                'explain_name' => 'استعادة المجموعة الأساسية المحذوفة'
            ],
            [
                'name' => 'BasicGroup-force-delete',
                'guard_name' => 'web',
                'explain_name' => 'حذف المجموعة الأساسية نهائياً'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('تم إنشاء صلاحيات المجموعات الأساسية بنجاح!');
        $this->command->info('يمكنك الآن إضافة المجموعات الأساسية من واجهة الإدارة.');
    }
}
