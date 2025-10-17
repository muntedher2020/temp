<?php

namespace App\Http\Controllers\JobTitles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobTitles\JobTitles as JobTitleModel;

class JobTitlePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:jobtitle-export-pdf');
    }

    /**
     * Show print-friendly page for JobTitles
     */
    public function printView()
    {
        try {
            $data = JobTitleModel::all();

            return view('exports.jobtitles_print', [
                'data' => $data,
                'title' => 'تقرير العنوان الوظيفي',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}