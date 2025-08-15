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
        
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' =>  $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60
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
    
}//class