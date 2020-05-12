<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $date = Carbon::now()->format('d/m/Y');
        return view('frontend.schedules.schedules', compact('date'));
    }
}