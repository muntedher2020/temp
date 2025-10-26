<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\ModuleGenerator\ModuleGeneratorController;
use App\Http\Controllers\Users\UsersAccounts\UsersAccountsController;
use App\Http\Controllers\PermissionsRoles\Roles\AccountRolesController;
use App\Http\Controllers\Users\CustomersAccounts\CustomersAccountsController;
use App\Http\Controllers\PermissionsRoles\Permissions\AccountPermissionsController;
use App\Http\Controllers\Users\AdministratorsAccounts\AdministratorsAccountsController;
use App\Http\Controllers\ReportGenerator\ReportGeneratorController;
use App\Http\Controllers\ReportGenerator\ReportGeneratorPdfController;

Route::middleware(['auth', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Dashboard - يتطلب صلاحية عرض لوحة التحكم
    Route::GET('/', [DashboardController::class, 'index'])->name('Dashboard')->middleware('can:Dashboards');

    // Roles & Permission - يتطلب صلاحيات محددة
    Route::GROUP(['prefix' => 'Permissions&Roles'], function () {
        Route::RESOURCE('Account-Permissions', AccountPermissionsController::class)->middleware('can:permission-list');
        Route::RESOURCE('Account-Roles', AccountRolesController::class)->middleware('can:role-list');
    });

    Route::RESOURCE('Administrators-Accounts', AdministratorsAccountsController::class)->middleware('can:user-list');
    Route::RESOURCE('Users-Accounts', UsersAccountsController::class)->middleware('can:user-list');
    Route::RESOURCE('Customers-Accounts', CustomersAccountsController::class)->middleware('can:user-list');

    // Module Generator - للمطورين والمسؤولين فقط
    Route::GET('Module-Generator', [ModuleGeneratorController::class, 'index'])->name('Module-Generator')->middleware('role:OWNER|Administrator');
    // إدارة الوحدات
    Route::view('/ModuleManager', 'content.ModuleManager.module-manager')->name('ModuleManager')->middleware(['auth', 'role:OWNER|Administrator']);

    // إدارة المجموعات الأساسية - Livewire Only
    Route::get('/basic-groups', function () {
        return view('content.basic-groups.index');
    })->name('basic-groups.index')->middleware('can:BasicGroup-list');

    // Data Management System - نظام إدارة البيانات (Livewire Only)
    Route::get('/data-management', function () {
        return view('content.data-management.index');
    })->name('data-management.index')->middleware(['auth', 'can:data-management-access']);

    // Report Generator - مولد التقارير المتقدم
    Route::GET('report-generator', [ReportGeneratorController::class, 'index'])->name('report-generator.index')->middleware('can:report-generator-access');
    Route::GET('report-generator/export-excel', [ReportGeneratorController::class, 'exportExcelDirect'])->name('report-generator.export.excel');
    Route::GET('report-generator/export-pdf', [ReportGeneratorPdfController::class, 'exportPdf'])->name('report-generator.export.pdf')->middleware('can:report-generator-access');

    // Dashboard Builder - مصمم الداشبورد
    Route::get('/dashboard-builder', function () {
        return view('content.dashboard-builder.index');
    })->name('dashboard-builder.index')->middleware(['auth', 'can:dashboard-builder-access']);














});



