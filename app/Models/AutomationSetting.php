<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'tracking_enabled',
        'ready_notification_enabled',
        'pickup_reminder_enabled',
        'unpaid_reminder_enabled',
        'daily_summary_enabled',
        'weekly_summary_enabled',
        'overdue_alert_enabled',
        'pickup_reminder_delay_hours',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
