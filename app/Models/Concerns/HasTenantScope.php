<?php

namespace App\Models\Concerns;

use App\Scopes\TenantScope;

trait HasTenantScope
{
    public static function bootHasTenantScope(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}
