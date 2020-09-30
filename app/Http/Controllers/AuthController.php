<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\User;
use Validator;
use App\Http\Helper\ResponseBuilder;

class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {        
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric|unique:users',
            'password' => 'required',
            'user_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

    
        try {

            $user = new User;
            $user->mobile_number = $request->input('mobile_number');
            $user->user_code = $this->genUserCode();
            $user->user_type = $request->input('user_type');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);

            //$otp = $this->sendOTP($request->input('mobile_number'));
            $otp = '1111';

            if(! is_numeric($otp))
            {
                return response()->json(['message' => 'Failed to send OTP !'], 400);
            }


            $user->otp = $otp;
            $user->save();

            $credentials = $request->only(['mobile_number', 'password']);
            $token = Auth::attempt($credentials);

            //return successful response
            return response()->json([
                'token' => $token,
                'status' => 2,
                'data' => $user, 
                'message' => 'User registered successfully' 
            ]);

        } catch (\Exception $e) {
            //return error message
            return response()->json([
                'token' => '',
                'status' => 6,
                'data' => '', 
                'message' => 'Something went wrong !' 
            ]);
        }

    }

    public function verifyOTP(Request $request) 
    {
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric',
            'password' => 'required',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        
        $user = User::where('mobile_number', $request->input('mobile_number'))->first();
        
        if($user->otp == $request->input('otp'))
        {
            $otp_verify = "OTP verified";
            $user->is_otp_verified = 1;
            $user->save();
            $status = 2;
            $credentials = $request->only(['mobile_number', 'password']);
            $token = Auth::attempt($credentials);
        }
        else
        {
            $otp_verify = "OTP incorrect !";
            $status = 4;
            $token = '';
            $user = '';
        }

        //return successful response
        return response()->json([
            'token' => $token,
            'status' => $status,
            'data' => $user, 
            'message' => $otp_verify 
        ]);

    }

    public function resendOTP(Request $request) 
    {
        
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric',
        ]);

        if ($validator->fails()) 
        {
            $status = 3;
            $info =  'Validation failed';
            $data = $validator->errors();
            return ResponseBuilder::result($status, $info, $data);
        }

        $user = User::where('mobile_number', $request->input('mobile_number'))->first();
        
        if(! $user)
        {
            $status = 4;
            $info = "User not found !";
            $data = '';
            return ResponseBuilder::result($status, $info, $data);
            
        }
        else
        {
            //$otp = $this->sendOTP($request->input('mobile_number'));

            $otp = '1111';

            $user->otp = $otp;
            
            $user->save();
            
            $status = 2;
            $info = "Resend otp successfully";
            $data = $user;
            return ResponseBuilder::result($status, $info, $data);
                
        }
        $status = 6;
        $info =  'Something went wrong !';
        $data = '';
        return ResponseBuilder::result($status, $info, $data);

    }
    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request)
    {
        //validate incoming request 

        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric',
            'password' => 'required'
        ]);

        if ($validator->fails()) 
        {
            $status = 3;
            $info =  'Validation failed';
            $data = $validator->errors();
            return ResponseBuilder::result($status, $info, $data);    
        }

        $user = User::where('mobile_number', $request->input('mobile_number'))->first();

        if(! $user )
        {
            $status = 4;
            $info = "User not found !";
            $data = '';
            return ResponseBuilder::result($status, $info, $data);
            
        }

        if($user->is_otp_verified == 0)
        {
            $status = 3;
            $info = "OTP not verified !";
            $data = '';
            $user->delete();
            return ResponseBuilder::result($status, $info, $data);

        }

        $credentials = $request->only(['mobile_number', 'password']);

        if (! $token = Auth::attempt($credentials)) 
        {
            $status = 4;
            $info = "Incorrect password or mobile number !";
            $data = '';
            return ResponseBuilder::result($status, $info, $data);
        }
        else
        {
            //return $this->respondWithToken($token);
            $token = Auth::attempt($credentials);

            //return successful response
            return response()->json([
                'token' => $token,
                'status' => 2,
                'data' => $user, 
                'message' => 'User login successfully' 
            ]);
        }
        $status = 6;
        $info =  'Something went wrong !';
        $data = '';
        return ResponseBuilder::result($status, $info, $data);
    }

    public function sendOTP($mobile_number) 
    {
        $otp = rand( 1000 , 9999 );

        $apiKey = urlencode(env('TEXTLOCAL_API', ''));
        
        // Message details

        $numbers = array($mobile_number);
        $sender = urlencode('TXTLCL');
        $message = rawurlencode('Your OTP is '.$otp);
        
        $numbers = implode(',', $numbers);
        
        // Prepare data for POST request
        $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);
        
        // Send the POST request with cURL
        $ch = curl_init('https://api.textlocal.in/send/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_obj = json_decode($response);

        if($response_obj->status == 'success')
        {
            return $otp;
        }
        else
        {
            return 'fail';
        }
    }
    public function genUserCode(){
        $this->user_code = [
            'user_code' => 'ALG'.mt_rand(0,999)
        ];

        $rules = ['user_code' => 'unique:users'];

        $validate = Validator::make($this->user_code, $rules)->passes();

        return $validate ? $this->user_code['user_code'] : $this->genUserCode();
    }

    public function changePassword(Request $request)
    {
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required',
            'newPassword' => 'required',
        ]);

        if ($validator->fails()) 
        {
            $status = 3;
            $info =  'Validation failed';
            $data = $validator->errors();
            return ResponseBuilder::result($status, $info, $data);
        }

        $currentPassword = $request->currentPassword;
        $newPassword = $request->newPassword;
        $user = Auth::user();
        
        if (password_verify($currentPassword, $user->password)) 
        {
            $newPassword = app('hash')->make($newPassword);
            $user->password = $newPassword;
            $user->save();
            $status = 2;
            $info = "Password updated succesfully";
        } 
        else 
        {
            $status = 3;
            $info = "The current password does not match";
        }
        return ResponseBuilder::result($status, $info, $currentPassword);

    }
}