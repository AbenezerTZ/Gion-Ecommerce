<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Cart;
use App\Models\Catagory;
use App\Models\Comment;
use App\Models\Order;
use App\Models\Customersell;
use App\Notifications\EmailSeller;
use Exception;
use App\Notifications\SendEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Stripe;
use App\Notifications\SendTicketNumber;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\CardException;

use PDF;

class HomeController extends Controller
{
  public function index()
  {
    $product = Product::where('product_status', 'New')->where('quantity', '>', 0)->paginate(9);
    return view('home.userpage', ['product' => $product]);
  }
  public function redirect()
  {
    $usertype = Auth::user()->usertype; //Auth method  easy way to autenticate user and manage the session 'user()' method return the currently autenticated user
    $name = Auth::user()->name;
    $user = Auth::user();
    if ($usertype == '1') {
      $product = Product::count();
      $total_order = Order::count();
      $total_customer = User::where('usertype', '0')->count();
      $total_admin = User::where('usertype', '1')->count();
      $total_revenue = Order::where('payment_status', 'Paid')->sum('price');
      $total_deliverd = Order::where('delivery_status', 'Deliverd')->count();
      $total_comment = Comment::count();
      $total_processed = Order::where('delivery_status', 'Pending')->orWhere('delivery_status', 'Processing')->count();
      return view('admin.home', [
        'product' => $product, 'order' => $total_order, 'customer' => $total_customer,
        'admin' => $total_admin, 'revenue' => $total_revenue, 'deliverd' => $total_deliverd,
        'processing' => $total_processed, 'comment' => $total_comment
      ]);
    } else {
      $product = Product::where('quantity', '>', 0)->where('product_status', 'New')->paginate(9);
      return view('home.userpage', ['product' => $product]);
    }
  }
  public function red()
  {
    return view('red');
  }
  public function full_product()
  {
    $product = Product::where('quantity', '>', 0)->paginate(9);
    $category = Catagory::all();
    return view('home.fullproduct', ['product' => $product, 'category' => $category]);
  }
  public function seller_items()
  {
    if (Auth::user()) {
      $user = Auth::user();
      if ($user->address == null && $user->address == null) {
        return redirect('newl')->with('message', 'Let Us Finish Your Set Up First!!');
      } else {
        $currentUserId = Auth::id();
        $product = Customersell::where('quantity', '>', 0)
          ->where('verification', 'Verified')
          ->whereNotIn('user_id', [$currentUserId])
          ->paginate(9);
        $category = Catagory::all();
        return view('home.sellerfullproduct', ['product' => $product, 'category' => $category]);
      }
    } else {
      return redirect('login');
    }
  }
  public function category($name)
  {
    $product = Product::where('category', $name)->where('quantity', '>', 0)->paginate(9);
    $category = Catagory::all();
    return view('home.fullproduct', ['product' => $product, 'category' => $category]);
  }
  public function search_product(Request $req)
  {
    $category = Catagory::all();
    $searchtext = $req->search;
    $product = Product::where('title', 'LIKE', "%$searchtext%")
      ->orWhere('product_status', 'LIKE', "%$searchtext%")
      ->orWhere('category', 'LIKE', "$searchtext")
      ->orWhere('quantity', 'LIKE', "%$searchtext%")
      ->orWhere('price', 'LIKE', "%$searchtext%")
      ->orWhere('discount_price', 'LIKE', "%$searchtext%")->paginate(9);

    return view('home.fullproduct', ['product' => $product, 'category' => $category]);
  }
  public function search_seller_Item(Request $req)
  {
    $category = Catagory::all();
    $searchtext = $req->search;
    if ($searchtext) {
      $product = Customersell::where('title', 'LIKE', "%$searchtext%")
        ->orWhere('name', 'LIKE', "%$searchtext%")
        ->orWhere('title', 'LIKE', "%$searchtext%")
        ->orWhere('status', 'LIKE', "%$searchtext%")
        ->orWhere('category', 'LIKE', "$searchtext")
        ->orWhere('quantity', 'LIKE', "%$searchtext%")
        ->orWhere('price', 'LIKE', "%$searchtext%")->paginate(9);
      return view('home.sellerfullproduct', ['product' => $product, 'category' => $category]);
    } else {
      return redirect()->back()->with('Noresult', 'No Input Detected!!');
    }
  }
  public function product_details($id)
  {
    $product = Product::find($id);
    return view('home.product_details', ['product' => $product]);
  }
  public function product_detailseller($id)
  {
    $product = Customersell::find($id);
    return view('home.product_detailseller', ['product' => $product]);
  }
  public function store($category)
  {
    $product = Product::where('quantity', '>', 0)->where('category', $category)->get();
    return view('home.store', ['product' => $product]);
  }
  public function add_cart(Request $req, $id)
  {
    if (Auth::id()) { // Auth::id() check if user logged in or not if user logged in
      $user = Auth::user(); //get all data af the logged in user
      if ($user->address == null && $user->address == null) {
        return redirect('newl')->with('message', 'Let Us Finish Your Set Up First!!');
      } else {
        $product = Product::find($id);
        if ($product->quantity < $req->quantity) {
          return redirect()->back()->with('faild', "Sorry  We Dont Have Such Quantity For This Product in Our Store We only left {$product->quantity}. Please Visit us  Next Time!!");
        } else {
          //if user already add the product previouslly and want to add it again it will update it not add it again
          $product_exist_id = Cart::where('product_id', $id)->where('user_id', $user->id)->get('id')->first();
          if ($product_exist_id != null) {
            $cartadd = Cart::find($product_exist_id)->first();
            $cartadd->quantity = $cartadd->quantity + $req->quantity;
            $quantity = $cartadd->quantity;
            if ($product->discount_price != null) {
              $cartadd->price = $product->discount_price * $quantity;
            } else {
              $cartadd->price = $product->price * $quantity;
            }
            $product->quantity = $product->quantity - $req->quantity; //inventary
            $cartadd->save();
            $product->save();
            return redirect()->back()->with('success', 'Product Added To Cart Successfully');
          } else {
            $cart = new Cart();
            $cart->name = $user->name; //save name of the spacific user
            $cart->email = $user->email;
            $cart->phone = $user->phone;
            $cart->address = $user->address;
            $cart->user_id = $user->id;
            $cart->product_title = $product->title;
            $discount = $product->discount_price;
            if ($discount) {
              $cart->price = $product->discount_price * $req->quantity;
            } else {
              $cart->price = $product->price * $req->quantity;
            }
            $cart->quantity = $req->quantity;
            $cart->image = $product->image;
            $cart->product_id = $product->id;
            $product->quantity = $product->quantity - $req->quantity; //inventary
            $product->save();
            $cart->save();
            return redirect('/#product')->with('success', 'Product Added To Cart Successfully');
          }
        }
      }
    } else {
      return redirect('login'); //path is creatd by laravel jetstream by default
    }
  }
  public function add_cartfull(Request $req, $id)
  {
    if (Auth::id()) { // Auth::id() check if user logged in or not if user logged in
      $user = Auth::user(); //get all data af the logged in user
      if ($user->address == null && $user->address == null) {
        return redirect('newl')->with('message', 'Let Us Finish Your Set Up First!!');
      } else {
        $product = Product::find($id);
        if ($product->quantity < $req->quantity) {
          return redirect()->back()->with('faild', "Sorry  We Dont Have Such Quantity For This Product in Our Store We only left {$product->quantity}. Please Visit us  Next Time!!");
        } else {
          //if user already add the product previouslly and want to add it again it will update it not add it again
          $product_exist_id = Cart::where('product_id', $id)->where('user_id', $user->id)->get('id')->first();
          if ($product_exist_id != null) {
            $cartadd = Cart::find($product_exist_id)->first();
            $cartadd->quantity = $cartadd->quantity + $req->quantity;
            $quantity = $cartadd->quantity;
            if ($product->discount_price != null) {
              $cartadd->price = $product->discount_price * $quantity;
            } else {
              $cartadd->price = $product->price * $quantity;
            }
            $product->quantity = $product->quantity - $req->quantity; //inventary
            $cartadd->save();
            $product->save();
            return redirect()->back()->with('success', 'Product Added To Cart Successfully');
          } else {
            $cart = new Cart();
            $cart->name = $user->name; //save name of the spacific user
            $cart->email = $user->email;
            $cart->phone = $user->phone;
            $cart->address = $user->address;
            $cart->user_id = $user->id;
            $cart->product_title = $product->title;
            $discount = $product->discount_price;
            if ($discount) {
              $cart->price = $product->discount_price * $req->quantity;
            } else {
              $cart->price = $product->price * $req->quantity;
            }
            $cart->quantity = $req->quantity;
            $cart->image = $product->image;
            $cart->product_id = $product->id;
            $product->quantity = $product->quantity - $req->quantity; //inventary
            $product->save();
            $cart->save();
            return redirect()->back()->with('success', 'Product Added To Cart Successfully');
          }
        }
      }
    } else {
      return redirect('login'); //path is creatd by laravel jetstream by default
    }
  }
  public function upd_cart(Request $req, $id)
  {
    $cart = Cart::find($id);
    $product = Product::find($cart->product_id);
    $prevquan = $cart->quantity;
    $net = $req->quantity - $prevquan;
    if ($net > $product->quantity) {
      if ($product->quantity == 0) {
        return redirect()->back()->with('faild', "Sorry  We Have Only the Specified Quantity For This Product Please Visit Us Next Time!!");
      }
      return redirect()->back()->with('faild', "Sorry  We Dont Have Such Quantity For This Product in Our Store You can only add up to  {$product->quantity} product");
    }
    $cart->quantity = $req->quantity;
    $quantity = $cart->quantity;
    if ($product->discount_price != null) {
      $cart->price = $product->discount_price * $quantity;
    } else {
      $cart->price = $product->price * $quantity;
    }
    if ($net > 0) {
      $product->quantity -= $net;
    } elseif ($net < 0) {
      $product->quantity += abs($net);
    }
    $product->save();
    $cart->save();
    return redirect()->back()->with('message', 'Your Cart is Updated Successfully');
  }
  public function show_cart()
  {
    if (Auth::id()) {
      $user = Auth::user();
      $data = Cart::where('user_id', $user->id)->get();
      if ($data->isEmpty()) {
        return view('home.showcart', ['messagen' => 'Your cart is empty!']);
      } else {
        $total = Cart::where('user_id', $user->id)->sum('price');
        return view('home.showcart', ['cartpro' => $data, 'total' => $total]);
      }
    } else {
      return redirect('login');
    }
  }
  public function show_order()
  {
    if (Auth::id()) {
      $user = Auth::user();
      $data = Order::where('user_id', $user->id)->get();
      return view('home.showorder', ['order' => $data]);
    } else {
      return redirect('login');
    }
  }
  public function deletecart($id)
  {
    $data = Cart::find($id);
    $data->delete();
    $product = Product::find($data->product_id);
    $product->quantity = $product->quantity + $data->quantity;
    $product->save();
    return redirect()->back()->with('message', 'The Item Deleted From The Cart Successfully');
  }
  public function deletecartao($userid)
  {
    $data = Cart::where('user_id', $userid)->delete();
    return redirect()->back();
  }
  public function cash_order()
  {
    $user = Auth::user();
    $userid = $user->id;
    $data = Cart::where('user_id', $userid)->get();
    $total = Cart::where('user_id', $user->id)->sum('price');
    $totalquantity = Cart::where('user_id', $user->id)->sum('quantity');
    $cart_products = [];
    $cart_productid = [];
    $cart_productquantity = [];
    $cart_image = [];
    $order = new Order();
    $order->name = $data[0]->name;
    $order->email = $data[0]->email;
    $order->phone = $data[0]->phone;
    $order->address = $data[0]->address;
    $order->user_id = $data[0]->user_id;
    foreach ($data as $item) {
      $cart_products[] = $item->product_title . ' (' . $item->quantity . ')';
      $cart_productid[] = $item->product_id;
      $cart_productquantity[] = $item->quantity;
      $cart_image[] = $item->image;
    }
    $total_products = implode(', ', $cart_products);
    $productsid = implode(', ', $cart_productid);
    $productsquantity = implode(', ', $cart_productquantity);
    $productimg = implode(', ', $cart_image);
    $order->product_title = $total_products;
    $order->eachquantity = $productsquantity;
    $order->product_id = $productsid;
    $order->quantity = $totalquantity;
    $order->price = $total;
    $order->image = $productimg;
    $order->payment_status = 'Cash on delivery';
    $order->delivery_status = 'Pending';
    $order->save();
    $this->print_pdf($order->id);
    $this->deletecartao($userid);
    return redirect()->back()->with('message', 'You Will Be Delivered Soon. Thank You For Shopping With Us!');
  }
  public function print_pdf($id)
  {
    $order = Order::find($id);
    $pdf = PDF::loadView('home.pdf', compact('order'));
    return $pdf->download('order_detail.pdf');
  }
  public function stripe($total)
  {
    return view('home.stripe', compact('total'));
  }
  public function payoption($total)
  {
    return view('home.Paymethods', compact('total'));
  }
  public function chapa($total)
  {
    return view('home.chapapage', compact('total'));
  }

  public function confirmchapapayment()
    {

      $user = Auth::user();

    $paymentParams = Session::get('payment_parameters');

    $Ordernumber = mt_rand(1000000000, 9999999999);

           //we  can get all information of the login user
    $userid = $user->id;
    $data = Cart::where('user_id', $userid)->get();
    $total = Cart::where('user_id', $user->id)->sum('price');
    $totalquantity = Cart::where('user_id', $user->id)->sum('quantity');
    $cart_products=[];
    $cart_productid=[];
    $cart_image=[];
    $order = new Order();
    $order->name = $data[0]->name;
    $order->email = $data[0]->email;
    $order->phone = $data[0]->phone;
    $order->address = $data[0]->address;
    $order->user_id = $data[0]->user_id;
    foreach ($data as $item) {
      $cart_products[] = $item->product_title . ' (' . $item->quantity . ')';
      $cart_productid[] = $item->product_id;
      $cart_productquantity[] = $item->quantity;
      $cart_image[] = $item->image;
  }
    $total_products = implode(', ', $cart_products);
    $productsid = implode(', ', $cart_productid);
    $productsquantity = implode(', ', $cart_productquantity);
    $productimg = implode(', ', $cart_image);
    $order->product_title = $total_products;
    $order->eachquantity = $productsquantity;
    $order->product_id = $productsid;
    $order->quantity = $totalquantity;
    $order->price = $total;
    $order->image = $productimg;
    $order->payment_status = 'paid';
    $order->delivery_status = 'Processing';
    $order->save();

    Session::put('Orderdetail', [
      'Ordernumber' =>   $Ordernumber,
      'button' => 'QR Code',
      'url' => 'https://github.com/',
  ]);

     Cart::where('user_id', $userid)->delete();

    return redirect('/email');

    }
    
  
  public function stripePost(Request $request, $total)
  {
    try {
      Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

      Stripe\Charge::create([
        "amount" => $total * 100,
        "currency" => "usd",
        "source" => $request->stripeToken,
        "description" => "Thanks For Payment"
      ]);
      $user = Auth::user(); //we  can get all information of the login user
      $userid = $user->id;
      $data = Cart::where('user_id', $userid)->get();
      $total = Cart::where('user_id', $user->id)->sum('price');
      $totalquantity = Cart::where('user_id', $user->id)->sum('quantity');
      $cart_products = [];
      $cart_productid = [];
      $cart_image = [];
      $order = new Order();
      $order->name = $data[0]->name;
      $order->email = $data[0]->email;
      $order->phone = $data[0]->phone;
      $order->address = $data[0]->address;
      $order->user_id = $data[0]->user_id;
      foreach ($data as $item) {
        $cart_products[] = $item->product_title . ' (' . $item->quantity . ')';
        $cart_productid[] = $item->product_id;
        $cart_productquantity[] = $item->quantity;
        $cart_image[] = $item->image;
      }
      $total_products = implode(', ', $cart_products);
      $productsid = implode(', ', $cart_productid);
      $productsquantity = implode(', ', $cart_productquantity);
      $productimg = implode(', ', $cart_image);
      $order->product_title = $total_products;
      $order->eachquantity = $productsquantity;
      $order->product_id = $productsid;
      $order->quantity = $totalquantity;
      $order->price = $total;
      $order->image = $productimg;
      $order->payment_status = 'paid';
      $order->delivery_status = 'Processing';
      $order->save();
      $this->deletecartao($userid);
      return redirect()->back()->with('success', 'Payment successful!');
    } catch (ApiConnectionException $exception) {
      // Stripe API connection exception
      return redirect()->back()->with('failed', 'No internet connection. Please check your network.');
    } catch (CardException $exception) {
      // Stripe card exception
      return redirect()->back()->with('failed', 'Card error. Please check your card details and try again.');
    } catch (Exception $exception) {
      // Other general exceptions
      return redirect()->back()->with('failed', 'An error occurred. Please try again later.');
    }
  }
  // public function cancel_order($id)
  // {
  //   $order = Order::find($id);
  //   $order->delete();
  //   return redirect()->back()->with('message', 'You have deleted your order successfully');
  // }
  public function cancel_order($id)
  {
    $order = Order::find($id);
    $productIDs = ltrim($order->product_id, ',');
    // Get the product IDs and quantities from the canceled order
    $productIDs = explode(', ', $order->product_id);
    $quantities = explode(', ', $order->eachquantity);

    // Update the quantities in the products table
    foreach ($productIDs as $key => $productID) {
      $product = Product::find($productID);
      if ($product) {
        $newQuantity = $product->quantity + $quantities[$key];
        $product->quantity = $newQuantity;
        $product->save();
      }
    }

    $order->delete();

    return redirect()->back()->with('message', 'You have successfully canceled your order.');
  }
  public function send_comment(Request $req)
  {
    if (Auth::id()) {
      $user = Auth::user();
      if ($user->address == null && $user->address == null) {
        return view('home.newlogin')->with('message', 'Let Us Finish Your Set Up First!!');
      }
      $post = new Comment();
      $post->name = $user->name;
      $post->user_id = $user->id;
      $post->comment = $req->comment;
      $post->save();
      return redirect('/#comment')->with('message', 'Thank You For Your Comment!!');
    }
  }
  public function contact()
  {
    return view('home.contact');
  }
  public function about()
  {
    $product = Product::all();
    return view('home.about', compact('product'));
  }
  public function sell()
  {
    if (Auth::user()) {
      $user = Auth::user();
      if ($user->address == null && $user->address == null) {
        return redirect('newl')->with('message', 'Let Us Finish Your Set Up First!!');
      } else {
        $category = Catagory::all();
        return view('home.sell', ['category' => $category]);
      }
    } else {
      return redirect('login');
    }
  }
  public function sellproduct(Request $req)
  {
    $user = Auth::user();
    $validatepro = $req->validate([
      'ItemDescription' => 'required|String',
      'ItemName' => 'required|String',
      'ItemQuantity' => 'required|Integer',
      'ItemPrice' => 'required|Integer',
      'ItemStatus' => 'required|String',
      'ItemPhoto' => 'required',
      'ItemCategory' => 'required|String',
    ]);
    $cuspro = new Customersell();
    $cuspro->name = $user->name;
    $cuspro->user_id = $user->id;
    $cuspro->email = $user->email;
    $cuspro->phone = $user->phone;
    $cuspro->address = $user->address;
    $cuspro->title = $validatepro['ItemName'];
    $cuspro->description = $validatepro['ItemDescription'];
    $cuspro->category = $validatepro['ItemCategory'];
    $cuspro->quantity = $validatepro['ItemQuantity'];
    $cuspro->price = $validatepro['ItemPrice'];
    if ($req->optionalPhone) {
      $cuspro->optional_Phone = $req->optionalPhone;
    } else {
      $cuspro->optional_Phone = null;
    }
    $cuspro->status = $validatepro['ItemStatus'];
    $image = $validatepro['ItemPhoto'];
    $imagename = time() . '.' . $image->getClientOriginalExtension(); //time() give image a unique name
    $validatepro['ItemPhoto']->move('custemersell', $imagename); //we will store image on 'custemersell folder of the public directory
    $cuspro->image = $imagename;
    $cuspro->verification  = 'Not Verified';
    $cuspro->save();
    return redirect()->back()->with('message', 'If Verified We Will Contact You With Your Phone or Email.Thank You For Shopping With Us!!!');
  }
  public function send_seller_email(Request $req, $id)
  {
    $order = Customersell::find($id);
    $user = Auth::user();
    $email = $req->body;
    if ($email) {
      if ($user->id != $order->user_id) {
        $details = [
          'greeting' => 'Hi How Are You',
          'firstline' => 'I Am Gion E-commerse User And I Have Seen You Item On Gion WebSite',
          'item' => 'Item name:' . " " . $order->title,
          'body' => $req->body,
          'lastline' => 'Please Respond on:' . " " . $user->email . " " . "or Use My Phone no:" . $user->phone . " " . 'As soon As Possible! Thank you :) ',
        ];
        try {
          Notification::send($order, new EmailSeller($details)); // Find email from the order table and send to that specific user 
          return redirect()->back()->with('message', 'Email Sent Successfully');
        } catch (Exception $e) {
          return redirect()->back()->with('Failed', 'Failed to send email. Please check your internet connection and try again.');
        }
      } else {
        return redirect()->back()->with('Failed', 'You cannot email yourself!!');
      }
    } else {
      return redirect()->back()->with('Noresult', 'No Input Entered!!');
    }
  }
}
