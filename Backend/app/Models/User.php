<?php   

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject {
    protected $table = "user_tbl";
    protected $fillable = [
        'email','password','last_login','ProfilePIcture'
    ];


    public function customer(){
        return $this->hasOne(Customer::class);
    }

    public function otps () {
        return $this->hasMany(UserOtp::class,'UserId','UserId');
    }

    public function getJWTIdentifier() {
        return $this->getKey();
    }
    public function getJWTCustomClaims(){
        return [];
    }
}