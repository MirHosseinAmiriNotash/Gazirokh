<?php   

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    protected $table = "user_tbl";
    protected $fillable = [
        'email','password','last_login','ProfilePIcture'
    ];


    public function customer(){
        return $this->hasOne(Customer::class);
    }
}