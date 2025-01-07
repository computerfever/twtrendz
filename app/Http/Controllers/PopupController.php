<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;

class PopupController extends Controller
{
    public function supportRenew(Request $request)
    {
        return view('popups.supportRenew');
    }
}
