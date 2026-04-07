<?php

namespace App\Models;

use App\Policies\PosPaymentMethodPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[UsePolicy(PosPaymentMethodPolicy::class)]
class PosPaymentMethod extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Payment method created',
                'updated' => 'Payment method updated',
                'deleted' => 'Payment method deleted',
            });
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getIcon(): string
    {
        return match ($this->code) {
            'CASH' => 'heroicon-o-banknotes',
            'CARD' => 'heroicon-o-credit-card',
            'TRANSFER' => 'heroicon-o-arrows-right-left',
            default => 'heroicon-o-currency-dollar',
        };
    }
}
