<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ChapaController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\OrderController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/',[HomeController::class ,'index']);
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/redirect',[HomeController::class ,'redirect'])->middleware('auth','verified');
Route::post("/sendsms/{id}",[SmsController::class,'sendsms']);
Route::get('auth/login', [RegisterController::class, 'googlepagelogin']);
// Route::get('auth/google/signup/callback', [RegisterController::class, 'googlecallbacklogin'])->name('auth.google.signup.callback');
Route::get('auth/google/callback', [RegisterController::class, 'googlecallbacklogin']);
Route::post('auth/google', [RegisterController::class, 'googlepage']);
Route::post('newlogin', [RegisterController::class, 'newlogin']);
Route::get('newl', [RegisterController::class, 'newlogin2']);
Route::post('regnew', [RegisterController::class, 'registerUser']);
// Route::get('auth/google/callback', [RegisterController::class, 'googlecallback']);
Route::get('/red',[HomeController::class ,'red']);
Route::get('/view_catagory',[AdminController::class,'view_catagory']);
Route::post('/add_catagory',[AdminController::class,'add_catagory']);
Route::get('delete/{id}',[AdminController::class,'delete']);
Route::get('/view_product',[AdminController::class,'view_product']);
Route::get('/view_customer',[AdminController::class,'view_customer']);
Route::post('/add_product',[AdminController::class,'add_product']);
Route::get('show_product',[AdminController::class,'show_product']);
Route::get('deletepro/{id}',[AdminController::class,'Deletepro']);
Route::get('deletecustpro/{id}',[AdminController::class,'Deletecustpro']);
Route::get('editpro/{id}',[AdminController::class,'EditPro']);
Route::get('editcust/{id}',[AdminController::class,'Editcust']);
Route::post('updpro/{id}',[AdminController::class,'Update']);
Route::post('updcust/{id}',[AdminController::class,'Updcust']);
Route::get('change/{id}',[AdminController::class,'Change']);
Route::get('/order',[AdminController::class,'order']);
Route::get('/print_pdf/{id}',[AdminController::class,'print_pdf']);
Route::get('/send_email/{id}',[AdminController::class,'send_email']);
Route::post('/send_user_email/{id}',[AdminController::class,'send_user_email']);
Route::post('/send_seller_email/{id}',[HomeController::class,'send_seller_email']);
Route::get('/search',[AdminController::class,'searchdata']);
Route::get('/searchpro',[AdminController::class,'searchpro']);
Route::get('/searchcustpro',[AdminController::class,'searchcustpro']);
Route::get('/comment',[AdminController::class,'comment']);
Route::get('/deletecom/{id}',[AdminController::class,'deletecom']);
Route::get('product_details/{id}',[HomeController::class,'product_details']);
Route::get('product_detailseller/{id}',[HomeController::class,'product_detailseller']);
Route::get('store/{category}',[HomeController::class,'store']);
Route::get('back',[HomeController::class,'back']);
Route::post('add_cart/{id}',[HomeController::class,'add_cart']);
Route::post('add_cartfull',[HomeController::class,'add_cartfull'])->name('add_cartfull');
Route::post('upd_cart/{id}',[HomeController::class,'upd_cart']);
Route::get('/show_cart',[HomeController::class,'show_cart'])->name('show_cart');
Route::get('/show_order',[HomeController::class,'show_order']);
Route::get('/contact',[HomeController::class,'contact']);
Route::get('/about',[HomeController::class,'about']);
Route::get('/sell',[HomeController::class,'sell']);
Route::post('/sellproduct',[HomeController::class,'sellproduct']);
Route::get('/category/{name}',[HomeController::class,'category']);
Route::get('/deletecart/{id}',[HomeController::class,'deletecart']);
Route::get('/cancel_order/{id}',[HomeController::class,'cancel_order']);
Route::get('/cash_order',[HomeController::class,'cash_order']);
Route::post('/send_comment',[HomeController::class,'send_comment']);
Route::get('/full_product',[HomeController::class,'full_product']);
Route::get('/seller_items',[HomeController::class,'seller_items']);
Route::get('/search_product',[HomeController::class,'search_product']);
Route::get('/email',[OrderController::class,'email']);
Route::get('confirmchapapayment',[HomeController::class,'confirmchapapayment']);
Route::get('/search_seller_Item',[HomeController::class,'search_seller_Item']);
Route::get('/stripe/{total}',[HomeController::class,'stripe']);
Route::post('/stripe/{total}',[HomeController::class,'stripePost'])->name('stripe.post');
Route::get('createpaypal',[PayPalController::class,'createpaypal'])->name('createpaypal');
Route::get('processPaypal',[PayPalController::class,'processPaypal'])->name('processPaypal');
Route::get('processSuccess',[PayPalController::class,'processSuccess'])->name('processSuccess');
Route::get('processCancel',[PayPalController::class,'processCancel'])->name('processCancel');
Route::get('/payoption/{total}',[HomeController::class,'payoption']);
Route::get('/chapa/{total}',[HomeController::class,'chapa']);
// The route that the button calls to initialize payment

Route::post('/pay/{total}',[ChapaController::class,'initialize'])->name('pay');

// The callback url after a payment
Route::get('callback/{reference}', [ChapaController::class,'callback'])->name('callback');
