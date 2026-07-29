<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username()
    {
        return 'username'; // Menggunakan kolom username
    }

    protected function credentials(Request $request)
    {
        return [
            $this->username() => $request->input($this->username()),
            'password'         => $request->input('password'),
            'is_active'        => true,
        ];
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    protected function authenticated(Request $request, $user)
    {
        session()->flash('success', 'Selamat datang, '.$user->name.'!');
    }

    public function redirectTo()
    {
        $role = auth()->user()->role;

        switch ($role) {
            case 'owner':
                return '/barang';
            case 'admin':
                return '/kategori';
            case 'kasir':
                return '/unit';
            default:
                return '/home';
        }
    }
}