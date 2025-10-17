<?php

namespace App\Http\Controllers\EducationalLevels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EducationalLevels\EducationalLevels as EducationalLevelModel;

class EducationalLevelPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:educationallevel-export-pdf');
    }

    /**
     * Show print-friendly page for EducationalLevels
     */
    public function printView()
    {
        try {
            $data = EducationalLevelModel::all();

            return view('exports.educationallevels_print', [
                'data' => $data,
                'title' => 'تقرير التحصيل العلمي',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}