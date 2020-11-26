<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;
use App\Message; 

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
            $message->user_id = Auth::id();
            $message->seller_id = $request->seller_id;
            $message->order_id = $request->order_id;
            $message->message = $request->message;
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
}
