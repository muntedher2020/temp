<?php

namespace App\Http\Controllers\CourseCandidates;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseCandidates\CourseCandidates as CourseCandidateModel;

class CourseCandidatePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:coursecandidate-export-pdf');
    }

    /**
     * Show print-friendly page for CourseCandidates
     */
    public function printView()
    {
        try {
            $data = CourseCandidateModel::all();

            return view('exports.coursecandidates_print', [
                'data' => $data,
                'title' => 'تقرير المتدربين والمرشحين',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}