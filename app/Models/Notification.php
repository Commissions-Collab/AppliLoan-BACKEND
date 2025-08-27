<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'notifiable_id',
        'notifiable_type',
        'is_read',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
