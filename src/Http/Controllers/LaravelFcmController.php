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
        
    }

    public function update(Request $request)
    {
        $request->device->update([
            'is_active' => $request->is_active
        ]);
        return response()->json();
    }

    public function store(Request $request)
    {
        $request->input('device')->update([
            'push_token' => $request->push_token
        ]);
        
        return response()->json();
    }
}