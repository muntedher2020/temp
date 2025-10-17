<?php
namespace App\Http\Controllers\Trainers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class TrainerController extends Controller
{
    public function index()
    {
        return view('content.Trainers.index');
    }
}