<?php

namespace App\Http\Controllers\TrainingDomains;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainingDomains\TrainingDomains as TrainingDomainModel;

class TrainingDomainPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:trainingdomain-export-pdf');
    }

    /**
     * Show print-friendly page for TrainingDomains
     */
    public function printView()
    {
        try {
            $data = TrainingDomainModel::all();

            return view('exports.trainingdomains_print', [
                'data' => $data,
                'title' => 'تقرير المجال التدريبي',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}