<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class member extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone_number',
        'address'
    ];



    public function user(){
        return $this -> belongsTo(User::class);
    }
}
