<?php

namespace App\Http\Controllers\Venues;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venues\Venues as VenueModel;

class VenuePrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:venue-export-pdf');
    }

    /**
     * Show print-friendly page for Venues
     */
    public function printView()
    {
        try {
            $data = VenueModel::all();

            return view('exports.venues_print', [
                'data' => $data,
                'title' => 'تقرير مكان انعقاد الدورة',
                'generated_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تحضير صفحة الطباعة: ' . $e->getMessage()], 500);
        }
    }
}