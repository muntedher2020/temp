<?php

namespace App\Http\Controllers\Departments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Departments\Departments as DepartmentModel;

class DepartmentPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:department-export-pdf');
    }

    /**
     * Show print-friendly page for Departments
     */
    public function printView()
    {
        try {
            $data = DepartmentModel::all();

            return view('exports.departments_print', [
                'data' => $data,
                'title' => 'تقرير الاقسام',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}