<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ربط المستخدمين بالأدوار

        // المالك الرئيسي
        $owner = User::where('email', 'owner@system.com')->first();
        if ($owner) {
            $owner->assignRole('OWNER');
        }

        // muntedher - مالك النظام أيضاً
        $muntedher = User::where('email', 'mun@gmail.com')->first();
        if ($muntedher) {
            $muntedher->assignRole('OWNER');
        }

        // المدير العام
        $admin = User::where('email', 'admin@system.com')->first();
        if ($admin) {
            $admin->assignRole('Administrator');
        }

        // المشرف
        $supervisor = User::where('email', 'supervisor@system.com')->first();
        if ($supervisor) {
            $supervisor->assignRole('Supervisor');
        }

        // الموظف
        $employee = User::where('email', 'employee@system.com')->first();
        if ($employee) {
            $employee->assignRole('Employee');
        }

        // العميل
        $customer = User::where('email', 'customer@system.com')->first();
        if ($customer) {
            $customer->assignRole('Customer');
        }

        // المدير
        $manager = User::where('email', 'manager@system.com')->first();
        if ($manager) {
            $manager->assignRole('Manager');
        }

        // رئيس القسم
        $deptHead = User::where('email', 'depthead@system.com')->first();
        if ($deptHead) {
            $deptHead->assignRole('Department_Head');
        }

        // المحاسب
        $accountant = User::where('email', 'accountant@system.com')->first();
        if ($accountant) {
            $accountant->assignRole('Accountant');
        }

        // دعم فني
        $itSupport = User::where('email', 'itsupport@system.com')->first();
        if ($itSupport) {
            $itSupport->assignRole('IT_Support');
        }
    }
}
