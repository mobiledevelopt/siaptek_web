<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class Wait extends Controller
{
    public function Index():View
    {
        return view("contents.wait.index")
        ->with([
            "title" => "be Patiently!"
        ]);
    }
}
