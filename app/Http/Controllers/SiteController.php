<?php

namespace App\Http\Controllers;

class SiteController extends Controller
{
    public function home()
    {
        return view('home', [
            'site' => config('site'),
        ]);
    }

    public function privacy()
    {
        return view('privacy', [
            'site' => config('site'),
        ]);
    }
}
