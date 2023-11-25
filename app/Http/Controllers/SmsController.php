<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
use Illuminate\Support\Facades\Auth;
use Exception;
class SmsController extends Controller
{
    public function sendsms(Request $req, $id)
    {
        $user = Auth::user();
        $message = $req->message;

        if ($message) {
            $account_sid = getenv('TWILIO_SID');
            $auth_token = getenv('TWILIO_TOKEN');
            $twilio_number = getenv('TWILIO_PHONE');

            try {
                $client = new Client($account_sid, $auth_token);
                $message = $client->messages->create(
                    "+251927146743", // Recipient's phone number
                    [
                        'from' => $twilio_number,
                        'body' => 'Sent From Gion Customer' . $user->phone . ' ' . $message . ' ' . 'Please Contact Me!!'
                    ]
                );

                return redirect()->back()->with('message', "Message Sent Successfully!!");
            }  catch (Exception $exception) {
                return redirect()->back()->with('Failed', 'Failed to send SMS. Please check your internet connection and try again.');
            }
        } else {
            return redirect()->back()->with('Noresult', 'No Message Entered!!');
        }
    }
}
