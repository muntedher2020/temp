<?php
namespace App\Http\Controllers\JobGrades;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class JobGradeController extends Controller
{
    public function index()
    {
        return view('content.JobGrades.index');
    }
}