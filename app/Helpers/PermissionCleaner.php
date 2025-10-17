<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionCleaner
{
    /**
     * حذف جميع الصلاحيات المتعلقة بوحدة معينة
     *
     * @param string $moduleName
     * @return array
     */
    public static function deleteModulePermissions($moduleName)
    {
        $results = [
            'deleted_count' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            $modulePrefix = strtolower(\Illuminate\Support\Str::singular($moduleName));

            // البحث عن جميع الصلاحيات المتعلقة بالوحدة
            $permissions = Permission::where('name', 'like', $modulePrefix . '%')->get();

            foreach ($permissions as $permission) {
                try {
                    // حذف الصلاحية من جميع الأدوار
                    $rolesDetached = $permission->roles()->count();
                    $permission->roles()->detach();

                    // حذف الصلاحية من جميع المستخدمين
                    $usersDetached = $permission->users()->count();
                    $permission->users()->detach();

                    // حذف الصلاحية نفسها
                    $permissionName = $permission->name;
                    $permission->delete();

                    $results['deleted_count']++;
                    $results['details'][] = [
                        'permission' => $permissionName,
                        'roles_detached' => $rolesDetached,
                        'users_detached' => $usersDetached
                    ];

                } catch (\Exception $e) {
                    $results['errors'][] = "خطأ في حذف الصلاحية {$permission->name}: " . $e->getMessage();
                }
            }

            // تسجيل النتائج
            if ($results['deleted_count'] > 0) {
                Log::info("تم حذف {$results['deleted_count']} صلاحية للوحدة {$moduleName}", $results['details']);
            }

        } catch (\Exception $e) {
            $results['errors'][] = "خطأ عام في حذف صلاحيات الوحدة {$moduleName}: " . $e->getMessage();
            Log::error("خطأ في حذف صلاحيات الوحدة {$moduleName}", ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * حذف صلاحيات متعددة الوحدات
     *
     * @param array $moduleNames
     * @return array
     */
    public static function deleteMultipleModulePermissions($moduleNames)
    {
        $totalResults = [
            'total_deleted' => 0,
            'modules_processed' => 0,
            'errors' => [],
            'details' => []
        ];

        foreach ($moduleNames as $moduleName) {
            $moduleResults = self::deleteModulePermissions($moduleName);

            $totalResults['total_deleted'] += $moduleResults['deleted_count'];
            $totalResults['modules_processed']++;
            $totalResults['errors'] = array_merge($totalResults['errors'], $moduleResults['errors']);
            $totalResults['details'][$moduleName] = $moduleResults;
        }

        return $totalResults;
    }

    /**
     * تنظيف الصلاحيات الفارغة أو المعطلة
     *
     * @return array
     */
    public static function cleanOrphanedPermissions()
    {
        $results = [
            'cleaned_permissions' => 0,
            'errors' => []
        ];

        try {
            // البحث عن صلاحيات غير مرتبطة بأي دور أو مستخدم
            $permissions = Permission::doesntHave('roles')->doesntHave('users')->get();

            foreach ($permissions as $permission) {
                try {
                    $permission->delete();
                    $results['cleaned_permissions']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "خطأ في حذف الصلاحية الفارغة {$permission->name}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "خطأ في تنظيف الصلاحيات الفارغة: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * عرض تقرير مفصل عن صلاحيات وحدة معينة
     *
     * @param string $moduleName
     * @return array
     */
    public static function getModulePermissionsReport($moduleName)
    {
        $modulePrefix = strtolower(\Illuminate\Support\Str::singular($moduleName));
        $permissions = Permission::where('name', 'like', $modulePrefix . '%')->with(['roles', 'users'])->get();

        $report = [
            'module' => $moduleName,
            'total_permissions' => $permissions->count(),
            'permissions' => []
        ];

        foreach ($permissions as $permission) {
            $report['permissions'][] = [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'roles_count' => $permission->roles->count(),
                'users_count' => $permission->users->count(),
                'roles' => $permission->roles->pluck('name')->toArray(),
                'created_at' => $permission->created_at
            ];
        }

        return $report;
    }
}
