<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Policies\PosRegisterPolicy;
use Database\Factories\PosRegisterFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[UseFactory(PosRegisterFactory::class)]
#[UsePolicy(PosRegisterPolicy::class)]
class PosRegister extends Model
{
    /** @use HasFactory<\Database\Factories\PosRegisterFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'is_open',
        'current_cashier_id',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'opened_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'is_open', 'current_cashier_id', 'opened_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Register created',
                'updated' => 'Register updated',
                'deleted' => 'Register deleted',
            });
    }

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'register_id');
    }

    public function currentCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_cashier_id');
    }

    /*
     |--------------------------------------------------------------------------
     | Scopes
     |--------------------------------------------------------------------------
     */

    #[Scope]
    public function opened($query)
    {
        return $query->where('is_open', true);
    }

    #[Scope]
    public function closed($query)
    {
        return $query->where('is_open', false);
    }

    /*
     |--------------------------------------------------------------------------
     | Methods
     |--------------------------------------------------------------------------
     */

    public function open(User $user): PosSession
    {
        if ($this->is_open) {
            throw new \Exception(__('messages.register.open_error'));
        }

        $this->update([
            'is_open' => true,
            'current_cashier_id' => $user->id,
            'opened_at' => now(),
        ]);

        return $this->sessions()->create([
            'user_id' => $user->id,
            'start_cash' => 0,
            'opened_at' => now(),
            'status' => SessionStatus::Open,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'is_open' => false,
            'current_cashier_id' => null,
            'opened_at' => null,
        ]);
    }
}
