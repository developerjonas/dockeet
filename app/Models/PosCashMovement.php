<?php

namespace App\Models;

use App\Enums\CashMovementType;
use App\Observers\PosCashMovementObserver;
use Database\Factories\PosCashMovementFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosCashMovementObserver::class)]
#[UseFactory(PosCashMovementFactory::class)]
class PosCashMovement extends Model
{
    /** @use HasFactory<\Database\Factories\PosCashMovementFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'session_id',
        'type',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['session_id', 'type', 'amount', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Cash movement recorded',
                'updated' => 'Cash movement updated',
                'deleted' => 'Cash movement deleted',
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
}
