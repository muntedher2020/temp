<?php
namespace App\Http\Controllers\CourseCandidates;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class CourseCandidateController extends Controller
{
    public function index()
    {
        return view('content.CourseCandidates.index');
    }
}