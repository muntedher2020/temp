<?php
namespace App\Http\Controllers\Departments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class DepartmentController extends Controller
{
    public function index()
    {
        return view('content.Departments.index');
    }
}