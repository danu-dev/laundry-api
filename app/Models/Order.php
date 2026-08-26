<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'NEW';

    public const STATUS_WASHING = 'WASHING';

    public const STATUS_IRONING = 'IRONING';

    public const STATUS_READY = 'READY';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const PAYMENT_UNPAID = 'UNPAID';

    public const PAYMENT_PARTIAL = 'PARTIAL';

    public const PAYMENT_PAID = 'PAID';

    protected $fillable = [
        'business_id',
        'customer_id',
        'order_number',
        'status',
        'subtotal',
        'extras_total',
        'total',
        'payment_status',
        'estimated_completion_at',
        'ready_at',
        'completed_at',
        'tracking_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'estimated_completion_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(OrderExtra::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
