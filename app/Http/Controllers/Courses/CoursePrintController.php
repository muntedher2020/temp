<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Courses\Courses as CourseModel;

class CoursePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:course-export-pdf');
    }

    /**
     * Show print-friendly page for Courses
     */
    public function printView()
    {
        try {
            $data = CourseModel::all();

            return view('exports.courses_print', [
                'data' => $data,
                'title' => 'تقرير الدورات التدريبية',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}