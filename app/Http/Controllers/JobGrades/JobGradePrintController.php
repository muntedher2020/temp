<?php

namespace App\Http\Controllers\JobGrades;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobGrades\JobGrades as JobGradeModel;

class JobGradePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:jobgrade-export-pdf');
    }

    /**
     * Show print-friendly page for JobGrades
     */
    public function printView()
    {
        try {
            $data = JobGradeModel::all();

            return view('exports.jobgrades_print', [
                'data' => $data,
                'title' => 'تقرير الدرجة الوظيفية',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}