<?php

namespace App\Models;

use App\Enums\CashMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\SessionStatus;
use App\Observers\PosSessionObserver;
use App\Policies\PosSessionPolicy;
use Database\Factories\PosSessionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PosPayment;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosSessionObserver::class)]
#[UseFactory(PosSessionFactory::class)]
#[UsePolicy(PosSessionPolicy::class)]
class PosSession extends Model
{
    use HasFactory, LogsActivity;

    protected $with = ['register', 'user'];

    protected $fillable = [
        'register_id',
        'user_id',
        'start_cash',
        'end_cash',
        'status',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_cash' => 'decimal:2',
            'end_cash' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => SessionStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['register_id', 'user_id', 'start_cash', 'end_cash', 'status', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Session opened',
                'updated' => 'Session updated',
                'deleted' => 'Session deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'session_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    #[Scope]
    public function betweenDates($query, $start, $end)
    {
        return $query->whereBetween('opened_at', [$start, $end]);
    }

    #[Scope]
    public function forUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    #[Scope]
    public function open($query)
    {
        return $query->where('status', SessionStatus::Open);
    }

    #[Scope]
    public function closed($query)
    {
        return $query->where('status', SessionStatus::Closed);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function calculateExpectedCash(): float
    {
        $opening = $this->start_cash;
        
        $cashSales = PosPayment::whereHas('order', fn($q) => $q->where('session_id', $this->id)->where('status', OrderStatus::Completed))
            ->whereHas('paymentMethod', fn($q) => $q->where('code', 'CASH'))
            ->sum('amount');

        $cashIn = $this->cashMovements()->where('type', CashMovementType::CashIn)->sum('amount');
        $cashOut = $this->cashMovements()->where('type', CashMovementType::CashOut)->sum('amount');

        return $opening + $cashSales + $cashIn - $cashOut;
    }
}
