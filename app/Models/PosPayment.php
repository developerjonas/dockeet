<?php

namespace App\Models;

use App\Observers\PosPaymentObserver;
use Database\Factories\PosPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosPaymentObserver::class)]
#[UseFactory(PosPaymentFactory::class)]
class PosPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_id',
        'pos_payment_method_id',
        'amount',
        'reference_data',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reference_data' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'pos_payment_method_id', 'amount', 'reference_data'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Payment recorded',
                'updated' => 'Payment updated',
                'deleted' => 'Payment deleted',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PosPaymentMethod::class, 'pos_payment_method_id');
    }
}
