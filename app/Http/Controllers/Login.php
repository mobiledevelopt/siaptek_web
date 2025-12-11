<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;

class Login extends Controller
{
    public function Index()
    {
        // dd(date('Y-m-d H:i:s'));
        return (Auth::check()) ? redirect()->route('home') : view("contents.auth.login")->with(['title' => 'Login']);
    }

    public function Action(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::getLastAttempted();
            if ($user->active == 2) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda Non Aktifkan',
                ])->onlyInput('email');
            } else {
                $request->session()->regenerate();
                return redirect('/')
                    ->withSuccess('Access granted!');
            }
        }
        return back()->withErrors([
            'email' => 'Your provided credentials do not match in our records.',
        ])->onlyInput('email');
    }

    public function Logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login')
            ->withSuccess('You have logged out successfully!');
    }
}
