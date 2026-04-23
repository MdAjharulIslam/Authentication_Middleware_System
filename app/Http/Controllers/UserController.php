<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'age'=>'required',
            'role'=>'required',
            'password' => 'required|confirmed'
        ]);

        $user = User::create($data);

        if ($user) {
            return redirect()->route('loginPage');
        }

        return back();
    }


    public function login(Request $request){
        $credintials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(Auth::attempt($credintials)){
       
            return redirect()->route('dashboard');
        }else{
            return redirect()->route('loginPage');
        }
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('loginPage');
    }


}