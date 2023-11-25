<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
class GoogleController extends Controller
{
    

    public function googlepagelogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googlecallbacklogin(){
        try{
            $user=Socialite::driver('google')->user();
            $finduser=User::where('google_id',$user->id)->first();
            if($finduser){
                Auth::login($finduser);
                return redirect()->intended('redirect');
            }
            else{
                $newuser=User::create([
                    'name'=> $user->name,
                    'email'=> $user->email,
                    'google_id'=>$user->id,
                    'password'=> encrypt('123456dumy'),
                ]);
                Auth::login( $newuser);
                return redirect()->intended('redirect');
            }
        }
        catch(Exception $e){
            dd($e->getMessage());
        }
    }
}
