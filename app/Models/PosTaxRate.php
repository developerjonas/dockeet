<?php

namespace App\Models;

use App\Policies\PosTaxRatePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[UsePolicy(PosTaxRatePolicy::class)]
class PosTaxRate extends Model
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
                'created' => 'Tax rate created',
                'updated' => 'Tax rate updated',
                'deleted' => 'Tax rate deleted',
            });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pos_product_tax_rates');
    }
}
