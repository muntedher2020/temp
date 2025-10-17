<?php
namespace App\Http\Controllers\JobTitles;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class JobTitleController extends Controller
{
    public function index()
    {
        return view('content.JobTitles.index');
    }
}