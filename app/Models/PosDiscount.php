<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Observers\PosDiscountObserver;
use App\Policies\PosDiscountPolicy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(PosDiscountObserver::class)]
#[UsePolicy(PosDiscountPolicy::class)]
class PosDiscount extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'start_date',
        'end_date',
        'is_active',
        'max_uses',
        'uses_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'type', 'value', 'start_date', 'end_date', 'is_active', 'max_uses'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Discount created',
                'updated' => 'Discount updated',
                'deleted' => 'Discount deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    #[Scope]
    public function active($query)
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    public function validDate($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    #[Scope]
    public function available($query)
    {
        return $query->active()->validDate()
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereRaw('uses_count < max_uses');
            });
    }

    #[Scope]
    public function upcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    #[Scope]
    public function expired($query)
    {
        return $query->whereNotNull('end_date')->where('end_date', '<', now());
    }
}
