<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Policies\ProductPolicy;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ObservedBy(ProductObserver::class)]
#[UseFactory(ProductFactory::class)]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'stock',
        'security_stock',
        'supplier_id',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'security_stock' => 'integer',
            'is_favorite' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'barcode', 'price', 'cost_price', 'stock', 'security_stock', 'supplier_id', 'is_favorite'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Product created',
                'updated' => 'Product updated',
                'deleted' => 'Product deleted',
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orderItems(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'product_id');
    }

    public function posCategory(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'pos_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PosBrand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PosUnit::class);
    }

    public function taxRates(): BelongsToMany
    {
        return $this->belongsToMany(PosTaxRate::class, 'pos_product_tax_rates');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PosSupplier::class, 'supplier_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(PosStockAdjustment::class, 'product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSearch($query, ?string $term = null)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    public function scopeAvailable($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeRecent($query)
    {
        return $query->latest();
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }
}
