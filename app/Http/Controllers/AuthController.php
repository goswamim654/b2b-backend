<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Helper\ResponseBuilder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use  App\User;
use App\PasswordReset;
use Validator;

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
            'mobile_number' => 'required|numeric|unique:users|min:10',
            'password' => 'required|min:6',
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
            'mobile_number' => 'required|numeric|min:10',
            'otp' => 'required|numeric|min:4',
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
            $user->is_otp_verified = 1;
            $user->save();

            //$credentials = $request->only(['mobile_number', 'password']);
            //$credentials = $request->only(['mobile_number', 'password']);
            //$token = Auth::attempt($credentials);

            //return successful response
            return response()->json([
                //'token' => $token,
                //'token_type' => 'bearer',
                //'expires_in' => Auth::factory()->getTTL() * 60 * 60 * 24 * 7,
                'status' => 2,
                'data' => $user, 
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

    public function resendOTP(Request $request) 
    {
        
        //validate incoming request 
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|numeric|min:10',
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
            'mobile_number' => 'required|numeric|min:10',
            'password' => 'required| min:6'
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
            $status = 4;
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
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60 * 60 * 24 * 7,
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
            'currentPassword' => 'required | min:6',
            'newPassword' => 'required | confirmed | min:6'
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

    public function sendResetPasswordLink(Request $request)
	{ 

        $input= array_map('trim', $request->all());
        
		try{

            \DB::beginTransaction();
            
			$validationRules=[
	            'email' => 'required|email',
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $status=3;
	            $content = $validator->errors();
                $message="Validation failed";
                return ResponseBuilder::result($status, $message, $content);
	        }

            $user = User::where('email', $input['email'])->first();
            
            if($user == null)
            {
                $status=4;
		        $content="";
                $message="No user found with the provided email";
                
            }
            else
            {
	        	$deleteExistingTokens = PasswordReset::where('email', $input['email'])->delete();

	        	$microTime=preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
		        $length=rand(20,30);
		        $tokenPre = bin2hex(random_bytes($length));
                $token = $tokenPre.$microTime;
                
                $password_reset = new PasswordReset();
                $password_reset->email = $input['email'];
		        $password_reset->token = $token;
		        $password_reset->ip = $request->ip();
                $password_reset->expire_at = \Carbon\Carbon::now()->addMinutes(60);
                
                
                $createUserToken = $password_reset->save();
                
                if($createUserToken)
                {
                    $passwordResetLink="https://www.alegralabs.com/mukesh/b2b/reset-password/".$token;
                    //$passwordResetLink="http://localhost:8080/reset-password/".$token;

		            $data['receiver_name']= $user->business_name;
		            $data['password_reset_link'] = $passwordResetLink;
                    $data['created_at'] = $password_reset['created_at'];
                    
		            $mailSent = Mail::send('emails.reset-password-mail', $data, function($message) use($user) {
                        
		            	$receiverEmail=trim($user->email);
					    $message->to($receiverEmail, $user->business_name)
					            ->subject('B2B password reset link');
					    $message->from('b2b@noreply.com','B2B');
                    });
                    
                    $status=2;
                    $content="";
                    $message="Reset password link sent successfully.";
                    
                }
                else
                {
		            $status=5;
		            $content="";
                    $message="Operation failed, could not generate and send the reset password link.";
                    
		        }
	        }

	        \DB::commit();
	    }
        catch(\Exception $e){
        	\DB::rollback();
        	$status=6;
	        $content=$e;
	        $message="Something went wrong !";
        }

        return ResponseBuilder::result($status, $message, $content);
    }
    
    public function resetPassword(Request $request)
	{ 
		$input=array_map('trim', $request->all());
		try{
			\DB::beginTransaction();
			$validationRules=[
	            'password' => 'required|confirmed|min:6',
	            'token' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $status = 3;
	            $content = $validator->errors();
	            $message = "Validation failed";
                return ResponseBuilder::result($status, $message, $content);
	        }

	        $tokenData= PasswordReset::where('token', $input['token'])->first();
            if($tokenData === null)
            {
                $status=4;
                $content="";
                $message="The link you are using is invalid. Please request a new reset password link.";
            }
            else
            {
		        
		        $expirationTime=$tokenData->expire_at;
                if (\Carbon\Carbon::now()->lte(\Carbon\Carbon::parse($expirationTime)))
                {

		        	$updateData['password']=Hash::make($input['password']);
		        	$updateData['ip']=$request->ip();
		        	$updateData['updated_at']=\Carbon\Carbon::now()->toDateTimeString();
		        	$updateRes=User::where('email', $tokenData->email)->update($updateData);
                    if($updateRes==true)
                    {
		        		$deleteExistingTokens=PasswordReset::where('token', $input['token'])->delete();
	            		$status=2;
		                $content="";
		                $message="Password is reset successfully";
                    }
                    else
                    {
	            		$status=5;
		                $content="";
		                $message="Operation failed, could not update the password";
	            	}
                }
                else
                {
                    
		        	$status=5;
		            $content="";
		            $message="Operation failed, the link you are using is expired.";
		        }
            }

	        \DB::commit();
	    }
        catch(\Exception $e){
        	\DB::rollback();
        	$status=6;
	        $content=$e;
	        $message="Something went wrong";
        }
        return ResponseBuilder::result($status, $message, $content);
	}

    public function changeMobileNumber($mobile_number) 
    {
        
        $user = User::find(Auth::id());

        if($user->mobile_number ==  $mobile_number || strpos($user->contact_numbers, $mobile_number) !== false)
        {
            $content = '';
            $status = 3;
            $message = "No changes in mobile number.";
            return ResponseBuilder::result($status, $message, $content);
        }
        $duplicate_mobile_check = User::where('mobile_number', '=', $mobile_number)->count();
        $allUsers = User::all();
        $count = 0;
        foreach($allUsers as $user_data)
        {
            if(strpos($user_data->contact_numbers, $mobile_number) !== false)
            {
                $count++;
            }
        }
        //$otp = $this->sendOTP($request->input('mobile_number'));
        if($duplicate_mobile_check > 0 || $count > 0) 
        {
            $content = '';
            $status = 3;
            $message = "Mobile number already exists.";
            return ResponseBuilder::result($status, $message, $content);
        }
        $otp = '1111';

        if(! is_numeric($otp))
        {
            $status=3;
            $content="";
            $message="Failed to sent OTP";
            return ResponseBuilder::result($status, $message, $content);
        }


        $user->otp = $otp;
        $user->save();
        $status=2;
        $content="";
        $message="OTP sent successfully";
        return ResponseBuilder::result($status, $message, $content);
    }


}