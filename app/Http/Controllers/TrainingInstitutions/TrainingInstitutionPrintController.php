<?php

namespace App\Http\Controllers\TrainingInstitutions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainingInstitutions\TrainingInstitutions as TrainingInstitutionModel;

class TrainingInstitutionPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:traininginstitution-export-pdf');
    }

    /**
     * Show print-friendly page for TrainingInstitutions
     */
    public function printView()
    {
        try {
            $data = TrainingInstitutionModel::all();

            return view('exports.traininginstitutions_print', [
                'data' => $data,
                'title' => 'تقرير مؤسسة المدرب',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}