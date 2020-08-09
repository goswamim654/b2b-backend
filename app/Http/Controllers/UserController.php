<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use  App\User;
use Illuminate\Http\Request;
use App\Http\Helper\ResponseBuilder;

class UserController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile()
    {

        $user = Auth::user();
        $user->contact_numbers = explode(',', $user->contact_numbers);  
        return response()->json(['user' => $user], 200);
    }
    /**
     * update company info.
     *
     * @return Response
     */
    public function profileUpdate(Request $request)
    {
        // validate incoming request 

        $this->validate($request, [
            'business_name' => 'required',
            'business_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'country' => 'required',
            'gst' => 'required',
            //'contact_numbers' => 'required',
            'email' => 'required| email',
        ]);

        $user = Auth::user();
        $user->business_name = $request->business_name;
        $user->business_category = $request->business_category;
        $user->business_address = $request->business_address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country = $request->country;
        $user->gst = $request->gst;
        if($request->contact_numbers) 
            $user->contact_numbers = implode(',', $request->contact_numbers);
        else
            $user->contact_numbers = ''; 
        $user->email = $request->email;
        $user->ip = $request->ip();

        // logo upload
        if($request->logo)
        {

            $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR;
            if($user->logo)
                unlink($destinationPath.$user->logo_name);
            $image_parts = explode(";base64,", $request->logo);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $logo = uniqid() .'.'. $image_type;
            $file = $destinationPath . $logo;
            $uploadPath = storage_path('uploads'. DIRECTORY_SEPARATOR);
            file_put_contents($file, $image_base64);
            
            $user->logo_name = $logo;
            $user->logo_url = url('/uploads/'.$logo);
        }

        $user->save();

        $status = 'success';
        $message = "Profile data updated succesfully";
        return ResponseBuilder::result($status, $message, $user);
    }
    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
         //return response()->json(['users' =>  User::all()], 200);
         $status = true;
         $info = "Listed user data succesfully";
         $data = User::all();
         return ResponseBuilder::result($status, $info, $data);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }

    }

}