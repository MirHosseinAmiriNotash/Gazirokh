<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Kavenegar\KavenegarApi;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller{

    public function requestOtp(Request $request) {
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/'
        ]);

        $otp = rand(100000,999999);
        $expiresAt = Carbon::now()->addMinutes(2);
         
        $user = User::firstOrCreate([
            'phone' => $request->phone],
            []
        );

        UserOtp::create([
           'UserId' => $user->UserId,
           'phone' => $request->phone,
           'otp_code' => $otp,
           'expires_at' =>  $expiresAt,
        ]);

        try{
            $api =new  KavenegarApi("307976355743335358594D5246445945346C52757759417261585878577532515937434F6A31696F4937493D");
            $sender = "2000660110";
            $message = "کد ورود شما: {$otp}";
            $api->Send($sender,$request->phone,$message);
        } catch(\Exception $e){
            return response()->json(['message'=> 'خطا در ارسال پیامک','error' => $e->getMessage()],500);
        }

        return response()->json([
            'message'=>'کد تایید ارسال شد'
        ]);

    }//requestOtp

    public function verifyOtp(Request $request){
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/',
             'otp_code' => 'required|numeric',
        ]);
        
        $otpRecord = UserOtp::where('phone', $request->phone)
            ->where('otp_code',$request->otp_code)
            ->where('is_used',false)
            ->where('expires_at','>',now())
            ->first();

        if(!$otpRecord){
            return response()->json(['message' => 'کد معتبر نیست یا منقضی شده'],400);    
        }

        $otpRecord->update(['is_used' => true]);
        $user = User::find($otpRecord->UserId);
        if(!$user){
            $user = User::create([
                'phone'=>$otpRecord->phone
            ]);
        }

        $user->update(['last_login' => Carbon::now()]);
        
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' =>  $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user
        ]);
        
    }//verifyOtp


    public function logout(){
        try {
            $token = JWTAuth::getToken();
            
            if(!$token) {
                return response()->json([
                    'error' => 'توکن ازسال نشده'
                ],400);
            
            }

            JWTAuth::invalidate($token);

            return response()->json([
                'message' => 'با موفقیت از حساب کاربری خارج شدید'
            ],200);
        } catch(TokenExpiredException $e){
            
            return response()->json([
                'error' => 'توکن منقضی شده است'
            ],401);
        } catch(TokenInvalidException $e){
             return response()->json([
                'error' => 'توکن معتبر نیست'
            ],401); 
        } catch(JWTException $e) {
            return response()->json([
                'error' => 'خطا در پردازش توکن'
            ], 500);
        }
    }//logout


    public function loginWithPassword(Request $request) {
        $request->validate([
            "login" => "required|string",
            "password" => "required|string"
            
        ]);


        $login  = $request->input('login');
        $password = $request->input('password');

        if(filter_var($login,FILTER_VALIDATE_EMAIL)) {
            $user  = User::where('email',$login)->first();
        }else if(preg_match('/^09[0-9]{9}$/',$login)){
            $user  = User::where('phone',$login)->first();
        } else {
            return response()->json(['message' => 'ایمیل یا شماره موبایل معتبر نیست'], 422);
        }

        if(!$user || $user->password !== $password) {
            return response()->json(['message' => 'نام کاربری یا رمز عبور اشتباه است'], 401);
        }

        $user->update(['last_login' => Carbon::now()]);

        $token = JWTAuth::FromUser($user);

        return response()->json([
            'token' => $token,
            'expires_at' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user
        ]);
    }//loginWithPassword


    public function forgotPasswordRequestOtp(Request $request){
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/'
        ]);

        $user = User::where('phone',$request->phone)->first();
        if(!$user){
            return response()->json(['message'=>'کاربری با این شماره پیدا نشد'],404);
        }
        $otp = rand(100000,999999);
        $expiredAt = Carbon::now()->addMinutes(2);
        
        UserOtp::create([
            'UserId' => $user->UserId,
            'phone' => $request->phone,
            'otp_code' => $otp,
            'expires_at'=> $expiredAt
        ]);
        
        try{
            $api =new  KavenegarApi("307976355743335358594D5246445945346C52757759417261585878577532515937434F6A31696F4937493D");
            $sender = "2000660110";
            $message = "کد اعتبار سنجی ورود به گزیروخ: {$otp}";
            $api->Send($sender,$request->phone,$message);
        } catch(\Exception $e){
            return response()->json(['message'=> 'خطا در ارسال پیامک','error' => $e->getMessage()],500);
        }

        return response()->json([
            'message'=>'کد تایید ارسال شد'
        ]);
    }//forgotPasswordRequestOtp

    public function forgotPasswordVerifyOtp(Request $request){
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/',
            'otp_code' => 'required|numeric',
        ]);

         $otpRecord = UserOtp::where('phone', $request->phone)
            ->where('otp_code',$request->otp_code)
            ->where('is_used',false)
            ->where('expires_at','>',now())
            ->first();

        if(!$otpRecord){
            return response()->json([
                'message' => 'کد معتبر نیست یا منقضی شده است'
            ,400]);
        }

        $otpRecord->update(['is_used' => true]);
        
        return response()->json([
            'message' => 'کد تایید شد، حالا رمز جدید را وارد کنید',
            'phone'   => $request->phone
        ]);
    }//forgotPasswordVerifyOtp

    public function resetPassword(Request $request){

        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/',
            'password' => 'required|string|min:6',
        ]);
        
        $user = User::where('phone',$request->phone)->first();
        if(!$user){
            return response()->json(['message' => 'کاربر یافت نشد'],404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر یافت'
        ]);

    }//resetPassword
    
}//class