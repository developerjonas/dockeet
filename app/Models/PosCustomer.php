<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Policies\PosCustomerPolicy;
use Database\Factories\PosCustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

#[UseFactory(PosCustomerFactory::class)]
#[UsePolicy(PosCustomerPolicy::class)]
class PosCustomer extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'dob',
        'city',
        'address',
        'notes',
        'loyalty_points',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'loyalty_points' => 'integer',
            // 'total_spent' => 'decimal:2', // Removed from casts as it is an accessor, not a column
            'dob' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'customer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    #[Scope]
    public function search($query, ?string $term = null)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('city', 'like', "%{$term}%");
    }

    #[Scope]
    public function active($query)
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    public function inactive($query)
    {
        return $query->where('is_active', false);
    }

    #[Scope]
    public function loyal($query)
    {
        return $query->where('loyalty_points', '>', 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()->where('status', OrderStatus::Completed)->sum('grand_total');
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }
}
