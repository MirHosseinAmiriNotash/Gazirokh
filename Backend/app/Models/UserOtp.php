<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model {
    protected $table = "user_otps";
    protected $fillable = [
        'UserId','phone','otp_code','expires_at','is_used'
    ];

    public function user() {
        return $this->belongsTo(User::class,'UserId','UserId');
    }

}//class