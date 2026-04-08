<?php

namespace SmartCms\Support\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasSlug
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $model) {
            if (! $model->slug && $model->shouldGenerateSlug()) {
                $model->slug = Str::slug($model->{$model->slugReferenceColumn()});
            }
        });

        static::updating(function (Model $model) {
            if (! $model->slug && $model->shouldGenerateSlug()) {
                $model->slug = Str::slug($model->{$model->slugReferenceColumn()});
            }
        });
    }

    public function slugReferenceColumn(): string
    {
        return 'name';
    }

    public function getSlugColumn(): string
    {
        return property_exists($this, 'slugColumn') ? $this->slugColumn : 'slug';
    }

    /**
     * @return Builder
     */
    public function scopeSlug(Builder $query, string $slug)
    {
        return $query->where($this->getSlugColumn(), $slug);
    }

    public function shouldGenerateSlug(): bool
    {
        return true;
    }
}
