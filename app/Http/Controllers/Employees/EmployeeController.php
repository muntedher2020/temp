<?php
namespace App\Http\Controllers\Employees;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class EmployeeController extends Controller
{
    public function index()
    {
        return view('content.Employees.index');
    }
}