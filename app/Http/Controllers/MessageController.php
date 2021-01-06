<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;
use App\Message; 
use App\User;
use App\Order; 
use App\Product; 
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $messages = Message::where('seller_id', '=', Auth::id())->with('user')->orderBy('id', 'DESC')->get();
        $status = 2;
        $message = "Messages retrived successfully";
        return ResponseBuilder::result($status, $message, $messages);

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
                'seller_id' => 'required',
                'order_id' => 'required',
                'message' => 'required'
            ]);
            

            if ($validator->fails()) {
                return response()->json([
                    'status' => 3, 
                    'data' =>  $validator->errors(),
                    'message' => 'Validation failed' 
                ]);
            }


            $message = new Message();

            if($request->user_type == 's')
            {
                $message->seller_id = Auth::id();
                $message->user_id = $request->seller_id;  
            }
            else
            {
                $order = Order::where('id', '=', $request->order_id)->update(['status' => 1, 'dispatched_date' => Carbon::now()->format('Y-m-d')]);
                $message->user_id = Auth::id(); 
                $message->seller_id = $request->seller_id;  
            }
            $message->order_id = $request->order_id;
            if($request->message == 'item not available')
            {
                $product_id = Order::where('id', '=', $request->order_id)->pluck('product_id');
                $product = Product::where('id', '=', $product_id)->first();
                //$message->message = 'Reference Order No:'. $order->product_id. $request->order_id.'.'.$request->message;
                $message->message = 'Reference Order No:'. $request->order_id.'.'.$product->name. ' with product ID: '. $product->id.' is not avaialable.';
            }
            else
            {
                $message->message = 'Reference Order No:'. $request->order_id.'.'.$request->message;
            }
            $message->user_type = $request->user_type;
            $message->message_type = $request->message_type;
            $message->save();

            \DB::commit();
            $status = 2;
            $message = "Message sent successfully";
            $content = "";

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
    public function update($id)
    {
        if($id == '')
        {
            $content = '';
            $status = 4;
            $message = "Message not found !";
        }
        else
        {
            $message = Message::find($id);
            $message->status = '1';
            $message->save();
            $content = Message::where('seller_id', '=', Auth::id())->with('user')->orderBy('id', 'DESC')->get();
            $status = 2;
            $message = "Messages retrived successfully";
        }
        
        return ResponseBuilder::result($status, $message, $content);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getMessageByOrder($id)
    {
        if($id == '')
        {
            $content = '';
            $status = 4;
            $message = "Message not found !";
        }
        else
        {
            $content = Message::where([['seller_id', '=', Auth::id()],['order_id', '=', $id]])->with('user')->orderBy('id', 'DESC')->get();
            $status = 2;
            $message = "Messages retrived successfully";
        }
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getNewMessagesCount($user_type) 
    {
        if($user_type == 's')
            $message_count = Message::where([['user_id', '=', Auth::id()], ['user_type', '=', $user_type], ['status', '=', 0]])->count();
        else
            $message_count = Message::where([['user_id', '=', Auth::id()], ['user_type', '=', $user_type], ['status', '=', 0]])->count();
        $status = 2;
        $message = "Messages count retrived successfully";
        return ResponseBuilder::result($status, $message, $message_count);
    }

    public function getSentMessages($user_type)
    {
        $messages =  Message::where([['user_id', '=', Auth::id()], ['user_type', '=', $user_type],['message_type', '=', 'o']])->get();
        if($messages)
        {
            $content = [];
            foreach($messages as $message) 
            {
                $data = [];
                $buyer_name =  User::where('id', '=', $message->seller_id)->pluck('business_name');
                $data['buyer_name'] = $buyer_name[0];
                $data['message'] = $message->message;
                $content[] = $data;
            }
            
            //$content = $messages;
            $status = 2;
            $message = "Seller sent messages retrived successfully";
            
        }
        else
        {
            $content = '';
            $status = 4;
            $message = "Message not found !"; 
        }
        return ResponseBuilder::result($status, $message, $content);
    }

    public function getInboxMessages($user_type)
    {
        $messages =  Message::where([['user_id', '=', Auth::id()], ['user_type', '=', $user_type],['message_type', '=', 'i']])->orderBy('id', 'DESC')->get();
        if($messages)
        {
            $content = [];
            foreach($messages as $message) 
            {
                $data = [];
                $buyer_name =  User::where('id', '=', $message->user_id)->pluck('business_name');
                $data['buyer_name'] = $buyer_name[0];
                $data['message'] = $message->message;
                $data['status'] = $message->status;
                $data['id'] = $message->id;
                $content[] = $data;
            }
            //$content = $messages;
            $status = 2;
            $message = "Inbox messages retrived successfully";  
        }
        else
        {
            $content = '';
            $status = 4;
            $message = "Message not found !"; 
            
        }
        return ResponseBuilder::result($status, $message, $content);
    }
    public function itemNotAvailable(Request $request)
    {
        try{
			\DB::beginTransaction();

            $validator = \Validator::make($request->all(), [
                'buyer_id' => 'required',
                'order_id' => 'required',
                'message' => 'required'
            ]);
            

            if ($validator->fails()) {
                return response()->json([
                    'status' => 3, 
                    'data' =>  $validator->errors(),
                    'message' => 'Validation failed' 
                ]);
            }


            $message = new Message();

            $message->seller_id = Auth::id();
            $message->user_id = $request->buyer_id;  
        
            $message->order_id = $request->order_id;
            
            $product_id = Order::where('id', '=', $request->order_id)->pluck('product_id');
            $product = Product::where('id', '=', $product_id)->first();
            
            $message->message = 'Reference Order No:'. $request->order_id.'.'.$product->name. ' with product ID: '. $product->id.' is not avaialable.';
            
           
            $message->user_type = $request->user_type;
            $message->message_type = $request->message_type;
            $message->save();

            \DB::commit();
            $status = 2;
            $message = "Message sent successfully";
            $content = "";

	    }
        catch(\Exception $e){
        	\DB::rollback();
        	$status=6;
	        $content=$e;
	        $message="Something went wrong";
        }
        return ResponseBuilder::result($status, $message, $content);
    }
 }
