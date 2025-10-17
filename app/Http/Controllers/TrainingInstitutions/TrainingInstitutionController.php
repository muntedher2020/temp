<?php
namespace App\Http\Controllers\TrainingInstitutions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class TrainingInstitutionController extends Controller
{
    public function index()
    {
        return view('content.TrainingInstitutions.index');
    }
}