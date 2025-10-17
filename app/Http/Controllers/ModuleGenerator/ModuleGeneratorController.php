<?php

namespace App\Http\Controllers\ModuleGenerator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModuleGeneratorController extends Controller
{
    public function index()
    {
        return view('content.ModuleGenerator.index');
    }
}
