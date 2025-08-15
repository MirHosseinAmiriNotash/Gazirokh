<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::post('/RequestOtp',[AuthController::class,'requestOtp']);
Route::post('/VerifyOtp',[AuthController::class,'verifyOtp']);
Route::middleware('auth:api')->post('/logout',[AuthController::class,'logout']);