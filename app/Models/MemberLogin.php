<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberLogin extends Model
{
    protected $fillable = [
        'member_id',
        'login_at'
    ];

    protected $table = 'member_login';

    public function member () {
        return $this->belongsTo(Member::class);
    }
}
