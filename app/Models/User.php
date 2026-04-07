<?php

namespace App\Models;

use App\Observers\UserObserver;
use App\Policies\UserPolicy;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasPanelShield, HasRoles, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pin_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'pin_code'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'User created',
                'updated' => 'User updated',
                'deleted' => 'User deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'customer_id');
    }

    public function sales(): HasManyThrough
    {
        return $this->hasManyThrough(
            PosOrder::class,
            PosSession::class,
            'user_id',
            'session_id',
            'id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The Rest
    |--------------------------------------------------------------------------
    */
    
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(['super_admin', 'admin', 'manager', 'cashier']);
        }

        return false;
    }

    public function routeNotificationForTwilio()
    {
        return $this->phone; 
    }
}
