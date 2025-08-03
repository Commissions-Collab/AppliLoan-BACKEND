<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSummary extends Model
{
   protected $table = 'analytics_summary';

    protected $fillable = [
        'report_date',
        'period_type',
        'total_sales',
        'total_loans',
        'total_payments',
        'active_members',
        'new_members',
        'inventory_value',
        'generated_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'generated_at' => 'datetime',
        'total_sales' => 'decimal:2',
        'total_loans' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'inventory_value' => 'decimal:2',
    ];
}
