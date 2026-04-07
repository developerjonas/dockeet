<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Observers\PosRefundObserver;
use App\Policies\PosRefundPolicy;
use Database\Factories\PosRefundFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosRefundObserver::class)]
#[UseFactory(PosRefundFactory::class)]
#[UsePolicy(PosRefundPolicy::class)]
class PosRefund extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_id',
        'customer_id',
        'user_id',
        'session_id',
        'amount',
        'reason',
        'status',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'customer_id', 'user_id', 'session_id', 'amount', 'reason', 'status', 'reference'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Refund created',
                'updated' => 'Refund updated',
                'deleted' => 'Refund deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PosCustomer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosRefundItem::class, 'refund_id');
    }
}
