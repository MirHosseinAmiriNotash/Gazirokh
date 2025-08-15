<?php   

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model {
    protected $table = "customer_tbl";
    protected $fillable = [
        'UserId','FirstName','LastName','national_id','DateOfBirth','address'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}