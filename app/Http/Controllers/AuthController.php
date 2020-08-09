<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\User;

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
        //validate incoming request 
        $this->validate($request, [
            'mobile_number' => 'required|numeric|unique:users',
            'password' => 'required',
        ]);

        try {

            $user = new User;
            $user->mobile_number = $request->input('mobile_number');
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
                'status' => 'success',
                'data' => $user, 
                'message' => 'User registered successfully' 
            ]);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'User Registration Failed!'], 409);
        }

    }

    public function verifyOTP(Request $request) 
    {
        //validate incoming request 
        $this->validate($request, [
            'mobile_number' => 'required|numeric',
            'password' => 'required',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('mobile_number', $request->input('mobile_number'))->first();
        
        if($user->otp == $request->input('otp'))
        {
            $otp_verify = "OTP verified";
            $user->is_otp_verified = 1;
            $user->save();
            $status = "success";
            $credentials = $request->only(['mobile_number', 'password']);
            $token = Auth::attempt($credentials);
        }
        else
        {
            $otp_verify = "OTP verify fail";
            $status = "fail";
            $token = '';
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
        $this->validate($request, [
            'mobile_number' => 'required|numeric'
        ]);

        $user = User::where('mobile_number', $request->input('mobile_number'))->first();
        
        if(! $user)
        {
            return response()->json(['message' => 'User not found !'], 400);
        }


        //$otp = $this->sendOTP($request->input('mobile_number'));

        $otp = '1111';

        if(! is_numeric($otp))
        {
            return response()->json(['message' => 'Failed to send OTP !'], 400);
        }

        $user->otp = $otp;
            
        $user->save();

        //return successful response
        return response()->json([
            'status' => 'success',
            'data' => $user, 
            'message' => 'Resend otp successfully' 
        ]);



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
        $this->validate($request, [
            'mobile_number' => 'required|numeric',
            'password' => 'required|string',
        ]);

        $user = User::where('mobile_number', $request->input('mobile_number'))->first();

        if($user->is_otp_verified == 0)
        {
            $message = 'OTP not verified';
            $user->delete();
            return response()->json(['message' => $message]);

        }

        $credentials = $request->only(['mobile_number', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        //return $this->respondWithToken($token);
        $token = Auth::attempt($credentials);

        //return successful response
        return response()->json([
            'token' => $token,
            'status' => 'success',
            'data' => $user, 
            'message' => 'User login successfully' 
        ]);
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



}