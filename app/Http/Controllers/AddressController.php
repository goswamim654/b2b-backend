<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Address;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $input= array_map('trim', $request->all());
        try{
			\DB::beginTransaction();

            $validator = \Validator::make($request->all(), [
                'order_id' => 'required',
                'business_address' => 'required',
                'city' => 'required',
                'zip' => 'required',
                'state' => 'required',
                'country' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 3, 
                    'data' =>  $validator->errors(),
                    'message' => 'Validation failed' 
                ]);
            }

            $address = Address::where('order_id', '=', $input['order_id'])->first();
            if ($address === null) {
                $address = new Address();
            }
            $address->user_id = Auth::id();
            $address->order_id = $input['order_id'];
            $address->business_address = $input['business_address'];
            $address->city = $input['city'];
            $address->zip = $input['zip'];
            $address->state = $input['state'];
            $address->country = $input['country'];
            $address->default_address = $input['default_address'];
            $address->save();

            \DB::commit();
            $status = 2;
            $message = "Address chnaged successfully";
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
        $content = Address::where('order_id', '=', $id)->get();
        $status = 2;
        $message = "Address retirved successfully";
        return ResponseBuilder::result($status, $message, $content);

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
    public function destroy($id)
    {
        //
    }
    public function getDefaultAddress()
    {
        $content = Address::where([['user_id', '=', Auth::id()],['default_address', '=', 1]])->get();
        $status = 2;
        $message = "Address retirved successfully";
        return ResponseBuilder::result($status, $message, $content);
    }
}
