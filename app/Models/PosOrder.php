<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Observers\PosOrderObserver;
use App\Policies\PosOrderPolicy;
use Database\Factories\PosOrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosOrderObserver::class)]
#[UseFactory(PosOrderFactory::class)]
#[UsePolicy(PosOrderPolicy::class)]
class PosOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $with = ['items'];

    protected $fillable = [
        'session_id',
        'customer_id',
        'discount_id',
        'reference',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'status' => OrderStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['session_id', 'customer_id', 'discount_id', 'reference', 'subtotal', 'tax_total', 'discount_total', 'grand_total', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Order created',
                'updated' => 'Order updated',
                'deleted' => 'Order deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PosCustomer::class, 'customer_id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(PosDiscount::class, 'discount_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PosRefund::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    #[Scope]
    public function betweenDates($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    #[Scope]
    public function forCustomer($query, PosCustomer $customer)
    {
        return $query->where('customer_id', $customer->id);
    }

    #[Scope]
    public function search($query, string $term)
    {
        return $query->where('reference', 'like', "%{$term}%")
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }

    #[Scope]
    public function completed($query)
    {
        return $query->where('status', OrderStatus::Completed);
    }

    #[Scope]
    public function parked($query)
    {
        return $query->where('status', OrderStatus::Parked);
    }

    #[Scope]
    public function today($query)
    {
        return $query->whereDate('created_at', today());
    }

    #[Scope]
    public function forSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    #[Scope]
    public function forCustomerId($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
