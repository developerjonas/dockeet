<?php

namespace App\Models;

use Database\Factories\PosRefundItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(PosRefundItemFactory::class)]
class PosRefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'order_item_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'restocked',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'restocked' => 'boolean',
        ];
    }

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */

    public function refund(): BelongsTo
    {
        return $this->belongsTo(PosRefund::class, 'refund_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(PosOrderItem::class, 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
