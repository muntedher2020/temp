<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الأدوار
        $roles = [
            'OWNER',
            'Administrator',
            'Supervisor',
            'Employee',
            'Customer',
            'Manager',           // مدير عام
            'Department_Head',   // رئيس قسم
            'Secretary',         // سكرتير
            'Clerk',            // كاتب
            'Reviewer',         // مراجع
            'Accountant',       // محاسب
            'Auditor',          // مدقق
            'Legal_Advisor',    // مستشار قانوني
            'IT_Support',       // دعم فني
            'Data_Entry',       // إدخال بيانات
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // تعيين دور OWNER للمستخدم الأساسي
        $user = User::find(4);
        if ($user) {
            $user->assignRole('OWNER');
        }

        // إنشاء صلاحيات شاملة للنظام
        $permissions = [
            // صلاحيات المشاريع
            'create-projects',
            'edit-projects',
            'delete-projects',
            'view-projects',
            'manage-projects',
            'approve-projects',
            'publish-projects',
            'archive-projects',

            // صلاحيات المستخدمين
            'create-users',
            'edit-users',
            'delete-users',
            'view-users',
            'manage-users',
            'activate-users',
            'deactivate-users',
            'reset-passwords',

            // صلاحيات الأدوار والصلاحيات
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-roles',
            'manage-roles',
            'assign-roles',
            'manage-permissions',

            // صلاحيات الإدارة العامة
            'view-dashboard',
            'view-reports',
            'export-data',
            'import-data',
            'manage-settings',
            'view-logs',
            'manage-backups',

            // صلاحيات المحتوى
            'create-content',
            'edit-content',
            'delete-content',
            'publish-content',
            'moderate-content',

            // صلاحيات التصدير والاستيراد
            'export-projects',
            'import-projects',
            'export-users',
            'import-users',

            // صلاحيات التحكم في النظام
            'system-maintenance',
            'database-management',
            'file-management',
            'api-access',

            // صلاحيات المراجعة والمراقبة
            'audit-logs',
            'monitor-activities',
            'review-changes',
            'approve-submissions',

            // صلاحيات التواصل
            'send-notifications',
            'manage-communications',
            'view-conversations',

            // صلاحيات الأمان
            'security-management',
            'access-control',
            'data-protection',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // تعيين جميع الصلاحيات لدور OWNER
        $ownerRole = Role::where('name', 'OWNER')->first();
        if ($ownerRole) {
            $ownerRole->givePermissionTo($permissions);
        }

        // تعيين صلاحيات محددة للمدير العام
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $adminPermissions = [
                'view-projects', 'create-projects', 'edit-projects', 'manage-projects', 'approve-projects',
                'view-users', 'create-users', 'edit-users', 'manage-users', 'activate-users', 'deactivate-users',
                'view-roles', 'create-roles', 'edit-roles', 'assign-roles',
                'view-dashboard', 'view-reports', 'export-data', 'manage-settings',
                'create-content', 'edit-content', 'publish-content', 'moderate-content',
                'audit-logs', 'monitor-activities', 'review-changes', 'approve-submissions'
            ];
            $adminRole->givePermissionTo($adminPermissions);
        }

        // تعيين صلاحيات للمشرف
        $supervisorRole = Role::where('name', 'Supervisor')->first();
        if ($supervisorRole) {
            $supervisorPermissions = [
                'view-projects', 'create-projects', 'edit-projects', 'approve-projects',
                'view-users', 'edit-users', 'activate-users',
                'view-dashboard', 'view-reports',
                'create-content', 'edit-content', 'moderate-content',
                'review-changes', 'approve-submissions'
            ];
            $supervisorRole->givePermissionTo($supervisorPermissions);
        }

        // تعيين صلاحيات للموظف
        $employeeRole = Role::where('name', 'Employee')->first();
        if ($employeeRole) {
            $employeePermissions = [
                'view-projects', 'create-projects', 'edit-projects',
                'view-users',
                'view-dashboard',
                'create-content', 'edit-content'
            ];
            $employeeRole->givePermissionTo($employeePermissions);
        }

        // تعيين صلاحيات للعميل
        $customerRole = Role::where('name', 'Customer')->first();
        if ($customerRole) {
            $customerPermissions = [
                'view-projects',
                'view-dashboard'
            ];
            $customerRole->givePermissionTo($customerPermissions);
        }

        // تعيين صلاحيات للمدير
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerPermissions = [
                'view-projects', 'create-projects', 'edit-projects', 'manage-projects',
                'view-users', 'create-users', 'edit-users',
                'view-dashboard', 'view-reports', 'export-data',
                'create-content', 'edit-content', 'publish-content',
                'review-changes', 'approve-submissions'
            ];
            $managerRole->givePermissionTo($managerPermissions);
        }

        // تعيين صلاحيات لرئيس القسم
        $deptHeadRole = Role::where('name', 'Department_Head')->first();
        if ($deptHeadRole) {
            $deptHeadPermissions = [
                'view-projects', 'create-projects', 'edit-projects',
                'view-users', 'edit-users',
                'view-dashboard', 'view-reports',
                'create-content', 'edit-content', 'moderate-content'
            ];
            $deptHeadRole->givePermissionTo($deptHeadPermissions);
        }
    }
}
