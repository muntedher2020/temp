<?php
namespace App\Http\Controllers\Venues;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class VenueController extends Controller
{
    public function index()
    {
        return view('content.Venues.index');
    }
}