<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RestController extends Controller
{
    public function startRest(Request $request)
    {
        session(['status' => '休憩中']);
        return redirect('/attendance');
    }

    public function endRest(Request $request)
    {
        session(['status' => '勤務中']);
        return redirect('/attendance');
    }
}
