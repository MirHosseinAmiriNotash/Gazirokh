<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::post('/RequestOtp',[AuthController::class,'requestOtp']);
Route::post('/VerifyOtp',[AuthController::class,'verifyOtp']);
Route::post('/login',[AuthController::class,'loginWithPassword']);
Route::post('/forgot-password/request-otp',[AuthController::class,'forgotPasswordRequestOtp']);
Route::post('/forgot-password/verify-otp',[AuthController::class,'forgotPasswordVerifyOtp']);
Route::post('/forgot-password/reset',[AuthController::class,'resetPassword']);
Route::middleware('auth:api')->post('/logout',[AuthController::class,'logout']);