<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Order;
use App\Notifications\Orderconfirmemail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Notification;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function email(){

        $user = Auth::user();
  
        $Orderdetail = Session::get('Orderdetail');
  
        $Ordernumber = $Orderdetail['Ordernumber'];
  
        $button =  $Orderdetail['button'];
  
        $url =  $Orderdetail['url'];
  
        Session::forget('Orderdetail');
  
        $customerid = User::find($user->id);
  
        $details=[
          'greeting' => 'Hello Customer',
          'firstline' => 'Welcome To Gion Ecommerce Site',
          'body' => 'The Ordrer number is down below  used as transaction reference for all products  you paid and you are asked while delivery You can get the QR code using the link Provided',
          'ticketnumber' =>  $Ordernumber,
          'button' => $button,
          'url' => $url,
          'lastline' => 'Have a great time!!',
       ];
       Notification::send($customerid, new Orderconfirmemail($details));//find email from the user table and send to that specific user 
       return redirect('/redirect')->with([
          'paysuccess' => 'Payment Successful!! We have already emailed your ticket reference number.And also you can download the QR Code there!',
          'ordernumber' => $Ordernumber,
      ]);
      }
}
