<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberEngagement extends Model
{
    protected $fillable = [
        'member_id',
        'page',
        'action',
        'engagement_at'
    ];

    protected $table = 'member_engagement';
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
