<?php

namespace App\Models;

use App\Enums\StockAdjustmentType;
use App\Observers\PosStockAdjustmentObserver;
use App\Policies\PosStockAdjustmentPolicy;
use Database\Factories\PosStockAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosStockAdjustmentObserver::class)]
#[UseFactory(PosStockAdjustmentFactory::class)]
#[UsePolicy(PosStockAdjustmentPolicy::class)]
class PosStockAdjustment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'product_id',
        'user_id',
        'quantity',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reason' => StockAdjustmentType::class,
            'quantity' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'user_id', 'quantity', 'reason', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Stock adjustment created',
                'updated' => 'Stock adjustment updated',
                'deleted' => 'Stock adjustment deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(PosLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(PosLocation::class, 'to_location_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    #[Scope]
    public function forProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    #[Scope]
    public function byReason($query, StockAdjustmentType $reason)
    {
        return $query->where('reason', $reason);
    }

    #[Scope]
    public function additions($query)
    {
        return $query->where('quantity', '>', 0);
    }

    #[Scope]
    public function reductions($query)
    {
        return $query->where('quantity', '<', 0);
    }
}
