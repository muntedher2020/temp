<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // تأكد من استيراد نموذج User

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء المستخدم المالك الرئيسي
        $owner = User::create([
            'id' => 1,
            'name' => 'مالك النظام',
            'email' => 'owner@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'OWNER',
            'status' => true,
        ]);

        // إنشاء مستخدم muntedher الأساسي
        $muntedher = User::create([
            'id' => 4,
            'name' => 'muntedher',
            'email' => 'mun@gmail.com',
            'password' => Hash::make('12345678'),
            'plan' => 'OWNER',
            'status' => true,
        ]);

        // إنشاء مدير عام
        $admin = User::create([
            'name' => 'أحمد المدير',
            'email' => 'admin@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Administrator',
            'status' => true,
        ]);

        // إنشاء مشرف
        $supervisor = User::create([
            'name' => 'سارة المشرفة',
            'email' => 'supervisor@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Supervisor',
            'status' => true,
        ]);

        // إنشاء موظف
        $employee = User::create([
            'name' => 'محمد الموظف',
            'email' => 'employee@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Employee',
            'status' => true,
        ]);

        // إنشاء عميل
        $customer = User::create([
            'name' => 'فاطمة العميلة',
            'email' => 'customer@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Customer',
            'status' => true,
        ]);

        // إنشاء مدير قسم
        $manager = User::create([
            'name' => 'خالد المدير',
            'email' => 'manager@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Manager',
            'status' => true,
        ]);

        // إنشاء رئيس قسم
        $deptHead = User::create([
            'name' => 'ليلى رئيسة القسم',
            'email' => 'depthead@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Department_Head',
            'status' => true,
        ]);

        // إنشاء محاسب
        $accountant = User::create([
            'name' => 'عمر المحاسب',
            'email' => 'accountant@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'Accountant',
            'status' => true,
        ]);

        // إنشاء دعم فني
        $itSupport = User::create([
            'name' => 'زياد التقني',
            'email' => 'itsupport@system.com',
            'password' => Hash::make('12345678'),
            'plan' => 'IT_Support',
            'status' => true,
        ]);
    }
}
