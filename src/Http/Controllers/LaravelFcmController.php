<?php

namespace WooSignal\LaravelFCM\Http\Controllers;

use Symfony\Component\Console\Exception\RuntimeException;
use WooSignal\LaravelFCM\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LaravelFcmController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware(function ($request, $next) {
        //     // $this->userDevice = $request->input('udevice');

        //     return $next($request);
        // });
    }

    public function update(Request $request)
    {

    }

    public function store(Request $request)
    {
        dd('here');
    }
}