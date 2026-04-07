<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosStockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_stock_transfer_id',
        'product_id',
        'quantity',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(PosStockTransfer::class, 'pos_stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
