<?php

namespace App\Http\Controllers\Trainers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trainers\Trainers as TrainerModel;

class TrainerPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:trainer-export-pdf');
    }

    /**
     * Show print-friendly page for Trainers
     */
    public function printView()
    {
        try {
            $data = TrainerModel::all();

            return view('exports.trainers_print', [
                'data' => $data,
                'title' => 'تقرير المدربين',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}