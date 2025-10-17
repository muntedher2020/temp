<?php
namespace App\Http\Controllers\EducationalLevels;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class EducationalLevelController extends Controller
{
    public function index()
    {
        return view('content.EducationalLevels.index');
    }
}