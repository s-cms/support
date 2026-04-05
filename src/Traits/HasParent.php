<?php

namespace SmartCms\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait HasParent
 */
trait HasParent
{
    /**
     * Get the parent relationship.
     */
    abstract public function parent(): BelongsTo;

    /**
     * Get the cached parent relationship.
     *
     * @return Model|null
     */
    public function getCachedParent()
    {
        return $this?->parent;
    }
}
