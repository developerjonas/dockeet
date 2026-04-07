<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Policies\PosStockTransferPolicy;
use Database\Factories\PosStockTransferFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[UseFactory(PosStockTransferFactory::class)]
#[UsePolicy(PosStockTransferPolicy::class)]
class PosStockTransfer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'reference',
        'from_location_id',
        'to_location_id',
        'status',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'from_location_id', 'to_location_id', 'status', 'notes', 'created_by', 'completed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Stock transfer created',
                'updated' => 'Stock transfer updated',
                'deleted' => 'Stock transfer deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(PosLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(PosLocation::class, 'to_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosStockTransferItem::class, 'pos_stock_transfer_id'); // Usually inferred as pos_stock_transfer_id by Laravel if convention is followed.
        // Wait, default FK for PosStockTransferItem::class is pos_stock_transfer_id.
        // But in Step 360 line 45: `return $this->hasMany(PosStockTransferItem::class);`.
        // I should keep it standard.
        // But explicit FK is safer if I don't check child model.
        // I will trust Laravel default unless needed.
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
