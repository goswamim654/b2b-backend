<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;
use App\Order;
use App\Product;
use App\ProductUnit;
use App\User;
use App\Message;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::where('user_id', '=', Auth::id())->with('user')->get();
        foreach( $orders as $order)
        {
            //dd($order->product->id);
            $products = Product::where('id', '=', $order->product_id)->with('photos')->with('units')->get();
            $order->product['units']  = $products;
            $seller =  User::where('id', '=', $order->seller_id)->get();
            $order['seller']  = $seller;
            $messageCount = Message::where([['order_id', '=', $order->id],['status', '=', 0]])->count();
            $order['messageCount'] = $messageCount;
        }
        $status = 2;
        $message = "Order list retrived successfully";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
  
		try{
			\DB::beginTransaction();

            $validator = \Validator::make($request->all(), [
                'order_details.*.seller_id' => 'required',
                'order_details.*.product_id' => 'required',
                "order_details.*.product_unit_id"  => "required|numeric",
                "order_details.*.quantity"  => "required|numeric",
                "order_details.*.price"  => "required|numeric",
            ]);
            

            if ($validator->fails()) {
                return response()->json([
                    'status' => 3, 
                    'data' =>  $validator->errors(),
                    'message' => 'Validation failed' 
                ]);
            }


            if(count($request->order_details) > 0) 
            {
                foreach($request->order_details as $order_detail)
                {
                    $product_unit = ProductUnit::where('id', '=', $order_detail['product_unit_id'])->first();
                    //dd($product_unit->stock);
                    if($product_unit->stock > $order_detail['quantity'])
                    {
                        $product_unit->stock = $product_unit->stock - $order_detail['quantity'];
                        $product_unit->save();
                        //dd($order_detail['product_id']);
                        $order = new Order();
                        $order->id = mt_rand(10000000, 99999999);
                        $order->user_id = Auth::id();
                        $order->product_id = $order_detail['product_id'];
                        $order->seller_id = $order_detail['seller_id'];
                        $order->product_unit_id = $order_detail['product_unit_id'];
                        $order->quantity = $order_detail['quantity'];
                        $order->price = $order_detail['price'];
                        $order->save();
                    }
                    else
                    {
                        $status = 4;
                        $message = "Order quantity exceeds the available quantity !";
                        $content = "";
                        return ResponseBuilder::result($status, $message, $content);
                    }
                    


                }
                \DB::commit();
                $status = 2;
                $message = "Order saved successfully";
                $content = "";
                return ResponseBuilder::result($status, $message, $content);
            }

	    }
        catch(\Exception $e){
        	\DB::rollback();
        	$status=6;
	        $content=$e;
	        $message="Something went wrong";
        }
        return ResponseBuilder::result($status, $message, $content);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteOrder($id)
    {
        if(!Order::find($id))
        {
            $status = 4;
            $message = "Order not found!";
            $content = "";
            return ResponseBuilder::result($status, $message, $content);

        }

        if(Order::find($id)->delete())
        {
            $status = 2;
            $message = "Order deleted successfully.";
            $content = "";
            return ResponseBuilder::result($status, $message, $content);
        }

        //Return error 400 response if delete was not successful
        $status = 6;
        $message = "Something went wrong !";
        $content = "";
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getOrders($user_id)
    {
        $orders = Order::where('user_id', '=', $user_id)->with('user')->get();
        foreach( $orders as $order)
        {
            //dd($order->product->id);
            $products = Product::where('id', '=', $order->product_id)->with('photos')->with('units')->get();

            $order->product['units']  = $products;
            $seller =  User::where('id', '=', $order->seller_id)->get();
            $order['seller']  = $seller;
            $messageCount = Message::where([['order_id', '=', $order->id],['status', '=', 0]])->count();
            $order['messageCount'] = $messageCount;
        }
        $status = 2;
        $message = "Order list retrived successfully";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getOrdersBySeller()
    {
        $orders = Order::where([['seller_id', '=',  Auth::id()],['status', '=', 0]])->with('user')->get();
        foreach( $orders as $order)
        {
            //dd($order->product->id);
            $products = Product::where('id', '=', $order->product_id)->with('photos')->with('units')->get();
            $order->product['units']  = $products;
            $buyer =  User::where('id', '=', $order->user_id)->get();
            $order['buyer']  = $buyer;
            $messageCount = Message::where([['order_id', '=', $order->id],['status', '=', 0]])->count();
            $order['messageCount'] = $messageCount;
        }
        $status = 2;
        $message = "Order list retrived successfully";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getOrdersByStatus()
    {
        $orders = Order::where('user_id', '=', Auth::id())->where('status', '=', 0)->with('user')->get();
        foreach( $orders as $order)
        {
            //dd($order->product->id);
            $products = Product::where('id', '=', $order->product_id)->with('photos')->with('units')->get();
            $order->product['units']  = $products;
            $buyer =  User::where('id', '=', $order->user_id)->get();
            $order['buyer']  = $buyer;
            $messageCount = Message::where([['order_id', '=', $order->id],['status', '=', 0]])->count();
            $order['messageCount'] = $messageCount;
            
        }
        $status = 2;
        $message = "Order list retrived successfully";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getOrdersByStatusView()
    {
        $orders = Order::where('user_id', '=', Auth::id())->where('order_view_status', '=', 0)->with('user')->get();
        $status = 2;
        $message = "New orders count";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }
    public function orderViewed() 
    {
        $orders = Order::where('user_id', '=', Auth::id())->where('order_view_status', '=', 0)->update(['order_view_status' => 1]);
        $status = 2;
        $message = "Orders viewed successfully";
        $content = $orders;
        return ResponseBuilder::result($status, $message, $content);
    }
}
