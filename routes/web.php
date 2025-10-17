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
use App\Http\Controllers\EducationalLevels\EducationalLevelController;
use App\Http\Controllers\EducationalLevels\EducationalLevelTcpdfExportController;
use App\Http\Controllers\EducationalLevels\EducationalLevelPrintController;
use App\Http\Controllers\Departments\DepartmentController;
use App\Http\Controllers\Departments\DepartmentTcpdfExportController;
use App\Http\Controllers\Departments\DepartmentPrintController;
use App\Http\Controllers\JobTitles\JobTitleController;
use App\Http\Controllers\JobTitles\JobTitleTcpdfExportController;
use App\Http\Controllers\JobTitles\JobTitlePrintController;
use App\Http\Controllers\JobGrades\JobGradeController;
use App\Http\Controllers\JobGrades\JobGradeTcpdfExportController;
use App\Http\Controllers\JobGrades\JobGradePrintController;
use App\Http\Controllers\TrainingInstitutions\TrainingInstitutionController;
use App\Http\Controllers\TrainingInstitutions\TrainingInstitutionTcpdfExportController;
use App\Http\Controllers\TrainingInstitutions\TrainingInstitutionPrintController;
use App\Http\Controllers\TrainingDomains\TrainingDomainController;
use App\Http\Controllers\TrainingDomains\TrainingDomainTcpdfExportController;
use App\Http\Controllers\TrainingDomains\TrainingDomainPrintController;
use App\Http\Controllers\Venues\VenueController;
use App\Http\Controllers\Venues\VenueTcpdfExportController;
use App\Http\Controllers\Venues\VenuePrintController;
use App\Http\Controllers\Employees\EmployeeController;
use App\Http\Controllers\Employees\EmployeeTcpdfExportController;
use App\Http\Controllers\Employees\EmployeePrintController;
use App\Http\Controllers\Trainers\TrainerController;
use App\Http\Controllers\Trainers\TrainerTcpdfExportController;
use App\Http\Controllers\Trainers\TrainerPrintController;
use App\Http\Controllers\Courses\CourseController;
use App\Http\Controllers\Courses\CourseTcpdfExportController;
use App\Http\Controllers\Courses\CoursePrintController;
use App\Http\Controllers\CourseCandidates\CourseCandidateController;
use App\Http\Controllers\CourseCandidates\CourseCandidateTcpdfExportController;
use App\Http\Controllers\CourseCandidates\CourseCandidatePrintController;

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










    Route::GET('EducationalLevels', [EducationalLevelController::class, 'index'])->name('EducationalLevels');
    Route::GET('EducationalLevels/export-pdf-tcpdf', [EducationalLevelTcpdfExportController::class, 'exportPdf'])->name('EducationalLevels.export.pdf.tcpdf');
    Route::GET('EducationalLevels/print-view', [EducationalLevelPrintController::class, 'printView'])->name('EducationalLevels.print.view');

    Route::GET('Departments', [DepartmentController::class, 'index'])->name('Departments');
    Route::GET('Departments/export-pdf-tcpdf', [DepartmentTcpdfExportController::class, 'exportPdf'])->name('Departments.export.pdf.tcpdf');
    Route::GET('Departments/print-view', [DepartmentPrintController::class, 'printView'])->name('Departments.print.view');

    Route::GET('JobTitles', [JobTitleController::class, 'index'])->name('JobTitles');
    Route::GET('JobTitles/export-pdf-tcpdf', [JobTitleTcpdfExportController::class, 'exportPdf'])->name('JobTitles.export.pdf.tcpdf');
    Route::GET('JobTitles/print-view', [JobTitlePrintController::class, 'printView'])->name('JobTitles.print.view');

    Route::GET('JobGrades', [JobGradeController::class, 'index'])->name('JobGrades');
    Route::GET('JobGrades/export-pdf-tcpdf', [JobGradeTcpdfExportController::class, 'exportPdf'])->name('JobGrades.export.pdf.tcpdf');
    Route::GET('JobGrades/print-view', [JobGradePrintController::class, 'printView'])->name('JobGrades.print.view');

    Route::GET('TrainingInstitutions', [TrainingInstitutionController::class, 'index'])->name('TrainingInstitutions');
    Route::GET('TrainingInstitutions/export-pdf-tcpdf', [TrainingInstitutionTcpdfExportController::class, 'exportPdf'])->name('TrainingInstitutions.export.pdf.tcpdf');
    Route::GET('TrainingInstitutions/print-view', [TrainingInstitutionPrintController::class, 'printView'])->name('TrainingInstitutions.print.view');

    Route::GET('TrainingDomains', [TrainingDomainController::class, 'index'])->name('TrainingDomains');
    Route::GET('TrainingDomains/export-pdf-tcpdf', [TrainingDomainTcpdfExportController::class, 'exportPdf'])->name('TrainingDomains.export.pdf.tcpdf');
    Route::GET('TrainingDomains/print-view', [TrainingDomainPrintController::class, 'printView'])->name('TrainingDomains.print.view');

    Route::GET('Venues', [VenueController::class, 'index'])->name('Venues');
    Route::GET('Venues/export-pdf-tcpdf', [VenueTcpdfExportController::class, 'exportPdf'])->name('Venues.export.pdf.tcpdf');
    Route::GET('Venues/print-view', [VenuePrintController::class, 'printView'])->name('Venues.print.view');


    Route::GET('Employees', [EmployeeController::class, 'index'])->name('Employees');
    Route::GET('Employees/export-pdf-tcpdf', [EmployeeTcpdfExportController::class, 'exportPdf'])->name('Employees.export.pdf.tcpdf');
    Route::GET('Employees/print-view', [EmployeePrintController::class, 'printView'])->name('Employees.print.view');



    Route::GET('Trainers', [TrainerController::class, 'index'])->name('Trainers');
    Route::GET('Trainers/export-pdf-tcpdf', [TrainerTcpdfExportController::class, 'exportPdf'])->name('Trainers.export.pdf.tcpdf');
    Route::GET('Trainers/print-view', [TrainerPrintController::class, 'printView'])->name('Trainers.print.view');

    Route::GET('Courses', [CourseController::class, 'index'])->name('Courses');
    Route::GET('Courses/export-pdf-tcpdf', [CourseTcpdfExportController::class, 'exportPdf'])->name('Courses.export.pdf.tcpdf');
    Route::GET('Courses/print-view', [CoursePrintController::class, 'printView'])->name('Courses.print.view');

    Route::GET('CourseCandidates', [CourseCandidateController::class, 'index'])->name('CourseCandidates');
    Route::GET('CourseCandidates/export-pdf-tcpdf', [CourseCandidateTcpdfExportController::class, 'exportPdf'])->name('CourseCandidates.export.pdf.tcpdf');
    Route::GET('CourseCandidates/print-view', [CourseCandidatePrintController::class, 'printView'])->name('CourseCandidates.print.view');
});



