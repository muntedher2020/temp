<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

if (!function_exists('hasRole')) {
    function hasRole($user, $roles) {
        return $user->hasAnyRole((array)$roles);
    }
}

class PermissionHelper
{
    /**
     * Get permissions for a specific module
     */
    public static function getPermissions($module)
    {
        $user = Auth::user();

        if (!$user) {
            return [
                'list' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
                'show' => false,
            ];
        }

        // نعطي صلاحيات مؤقتة للتطوير
        // سيتم تحديثها لاحقاً عند تشغيل الصلاحيات
        $permissions = [
            'list' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'show' => true,
        ];

        return $permissions;
    }

    /**
     * Check if user has any permission for module
     */
    public static function hasAnyPermission($module)
    {
        $permissions = self::getPermissions($module);
        return collect($permissions)->contains(true);
    }

    /**
     * Get all permissions for user
     */
    public static function getAllPermissions()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return [];
    }
}
