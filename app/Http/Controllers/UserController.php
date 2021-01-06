<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use  App\User;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
        $status = 2;
        $info = "Profile data retrived successfully";
        return ResponseBuilder::result($status, $info, $user);
    }
    /**
     * update company info.
     *
     * @return Response
     */
    public function profileUpdate(Request $request)
    {
        $user = User::find(Auth::id());

        if($user->mobile_number !==  $request->mobile_number || strpos($user->contact_numbers, $request->mobile_number) !== false)
        {
            
            $duplicate_mobile_check = User::where('mobile_number', '=', $request->mobile_number)->count();
            $allUsers = User::all();
            $count = 0;
            foreach($allUsers as $user_data)
            {
                if(strpos($user_data->contact_numbers, $request->mobile_number) !== false)
                {
                    $count++;
                }
            }
            if($duplicate_mobile_check > 0 || $count > 0) 
            {
                $content = '';
                $status = 4;
                $message = "Mobile number already exists.";
                return ResponseBuilder::result($status, $message, $content);
            }
        }
        // validate incoming request 

        $validator = Validator::make($request->all(), [
            'business_name' => 'required',
            'business_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'country' => 'required',
            'mobile_number' => 'required|numeric|digits:10',
            'gst' => 'required',
            //'contact_numbers' => 'required',
            'email' => 'required|email',
        ]);
        if($request->contact_numbers)
        {
            foreach( $request->contact_numbers as $contact_number)
            {
                if(preg_match('/^[0-9]{10}+$/', $contact_number))
                {
                    $validator->errors()->add(
                        'contact_numbers.*', 'Mobile number is not valid!'
                    );
                }
            }
        }
        if ($validator->fails()) 
        {
            $status = 3;
            $info =  'Validation failed';
            $data = $validator->errors();
            return ResponseBuilder::result($status, $info, $data);    
        }

        $user = Auth::user();
        $user->business_name = $request->business_name;
        $user->business_category = $request->business_category;
        $user->business_address = $request->business_address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country = $request->country;
        $user->gst = $request->gst;
        $user->mobile_number = $request->mobile_number;
        if($request->contact_numbers) 
            $user->contact_numbers = implode(',', $request->contact_numbers);
        else
            $user->contact_numbers = ''; 
        $user->email = $request->email;

        if($request->others)
            $user->other = $request->others;
        $user->ip = $request->ip();

        $sound = " ";
        $words = explode(" ", $request->business_name);
        foreach($words as $word)
        {
            $sound .= metaphone($word). " ";
        }

        $words = explode(" ", $request->business_category);
        foreach($words as $word)
        {
            $sound .= metaphone($word). " ";
        }
        $user->indexing = $sound;

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
        
        $user->status = 1;
        $user->save();

        $status = 2;
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
            $status = 2;
            $info = "User retrived succesfully";;
            return ResponseBuilder::result($status, $info, $user);

        } catch (\Exception $e) {
            $user = '';
            $status = 4;
            $info = "User not found";;
            return ResponseBuilder::result($status, $info, $user);
        }

    }

    public function getCities($state)
    {
        $cities = include(resource_path('appData/cities.php'));
        $status = 2;
        $info = "Retrived cities succesfully";
        $state = str_replace("%20", " ", $state);
        return ResponseBuilder::result($status, $info, $cities["$state"]);
    }

    public function getSellers()
    {
        
         $data = User::where([['user_type', '!=', 'b'],['status', '=', 1]])->get();
         if(count($data))
         {
            $status = 2;
            $info = "Listed seller data succesfully";
         }
         else
         {
            $data = '';
            $status = 4;
            $info = "Seller not found"; 
         }

         return ResponseBuilder::result($status, $info, $data);
    }

    public function getBuyers()
    {
        
         $data = User::where([['user_type', '!=', 's'],['status', '=', 1]])->get();
         if(count($data) > 0)
         {
            $status = 2;
            $info = "Listed buyer data succesfully";
         }
         else
         {
            $data = '';
            $status = 4;
            $info = "Buyers not found"; 
         }

         return ResponseBuilder::result($status, $info, $data);
    }

    public function testEmail(Request $request)
	{ 
		$data = array('name'=>"Mukesh Goswami", "body" => "Test mail");
    
		Mail::send('emails.resetpassword', $data, function($message) {
		    $message->to('goswamim654@gmail.com', 'Mukesh Goswami')
		            ->subject('b2b Testing Mail');
		    $message->from('ankitpro999@gmail.com','b2b');
		});

    }
    
    public function  updateUserType() {
        $user = Auth::user();
        $user->user_type = 'bs';
        $user->save();
        $status = 2;
        $message = "User type updated  successfully";
        return ResponseBuilder::result($status, $message, $user);
    }
    public function addNewMobileNumber(Request $request) 
    {
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric| min:10',
            'otp' => 'required|numeric|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        
        $user = User::find(Auth::id());
        if($request->mobile_number  == $user->mobile_number || strpos($user->contact_numbers, $request->mobile_number) !== false) 
        {
            $status = 3;
            $info = "Mobile number already exists.";
            return ResponseBuilder::result($status, $info, $user);
        }
        if($user->otp == $request->input('otp'))
        {
            $contact_numbers = $user->contact_numbers;
            if($contact_numbers)
                $contact_numbers .= ','.$request->mobile_number;
            else
                $user->contact_numbers = $request->mobile_number.',';
            $user->save();
            return response()->json([
                'status' => 2,
                'data' => '', 
                'message' => 'OTP verified' 
            ]);
        }
        else
        {
            $otp_verify = "OTP incorrect !";
            $status = 4;
            //$token = '';
            $user = '';
        }

        //return successful response
        return response()->json([
            'status' => $status,
            'data' => $user, 
            'message' => $otp_verify 
        ]);
    }

    public function deleteMobileNumber(Request $request)
    {
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric| min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        
        $user = User::find(Auth::id());

        $contact_numbers = $user->contact_numbers;

        if($contact_numbers)
        {
            $contact_numbers = str_replace($request->mobile_number, '', $contact_numbers);
            $user->contact_numbers = $contact_numbers;
            $user->save();
            $status = 2;
            $info = "Mobile number deleted successfully";
            return ResponseBuilder::result($status, $info, $user);
        }
        else
        {
            $user= '';
            $status = 3;
            $info = "No mobile number found";
            return ResponseBuilder::result($status, $info, $user);
        }
        

    }
    
    
}