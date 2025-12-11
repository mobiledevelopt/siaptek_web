<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class Passwd extends Controller
{
    public function Index(): View
    {
        return view("contents.passwd.form")
            ->with([
                'title' => 'Ganti Password'
            ]);
    }

    public function Action(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed'
        ]);

        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->password)
        ]);
        return back()->with("status", "Password changed successfully!");
    }
}
