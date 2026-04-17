<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function homePage(Request $request)
    {
        return view('auth.login');
//        return view('frontend.homepage');
    }

    public function plansPage(Request $request)
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return view('frontend.plans', compact('plans'));
    }
}
