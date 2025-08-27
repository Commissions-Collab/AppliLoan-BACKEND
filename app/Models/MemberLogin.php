<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLogin extends Model
{
    protected $fillable = [
        'member_id',
        'login_at'
    ];

    protected $table = 'member_login';

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
