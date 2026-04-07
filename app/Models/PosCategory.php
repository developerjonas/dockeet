<?php

namespace App\Models;

use App\Policies\PosCategoryPolicy;
use Database\Factories\PosCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UseFactory(PosCategoryFactory::class)]
#[UsePolicy(PosCategoryPolicy::class)]
class PosCategory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PosCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PosCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
            ->orWhere('description', 'like', "%{$term}%");
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
    public function parents($query)
    {
        return $query->whereNull('parent_id');
    }

    #[Scope]
    public function childrenOf($query, int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }
    
    #[Scope]
    public function sorted($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    #[Scope]
    public function recent($query)
    {
        return $query->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('category_images')
            ->useDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('webp');

        $this->addMediaConversion('optimized')
            ->width(800)
            ->height(800)
            ->format('webp');
    }

    public function getTotalProductsCountAttribute(): int
    {
        $ownCount = $this->products_count ?? $this->products()->count();

        $childrenCount = $this->children->sum(function ($child) {
            return $child->total_products_count;
        });

        return $ownCount + $childrenCount;
    }
}
