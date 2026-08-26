<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'CASH';

    public const METHOD_QRIS = 'QRIS';

    public const METHOD_TRANSFER = 'TRANSFER';

    public const STATUS_PAID = 'PAID';

    public const STATUS_PARTIAL = 'PARTIAL';

    protected $fillable = [
        'order_id',
        'amount',
        'method',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
