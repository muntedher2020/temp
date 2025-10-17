<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employees\Employees as EmployeeModel;

class EmployeePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee-export-pdf');
    }

    /**
     * Show print-friendly page for Employees
     */
    public function printView()
    {
        try {
            $data = EmployeeModel::all();

            return view('exports.employees_print', [
                'data' => $data,
                'title' => 'تقرير الموظفين',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}