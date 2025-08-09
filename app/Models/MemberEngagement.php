<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberEngagement extends Model
{
    protected $fillable = [
        'member_id',
        'page',
        'action',
        'engagement_at'
    ];

    protected $table = 'member_engagement';
    public function member () {
        return $this->belongsTo(Member::class);
    }
}
