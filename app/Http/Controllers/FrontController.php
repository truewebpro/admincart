<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function homePage(Request $request)
    {
        return view('auth.login');
//        return view('frontend.homepage');
    }
}
