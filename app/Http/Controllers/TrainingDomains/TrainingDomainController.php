<?php
namespace App\Http\Controllers\TrainingDomains;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class TrainingDomainController extends Controller
{
    public function index()
    {
        return view('content.TrainingDomains.index');
    }
}