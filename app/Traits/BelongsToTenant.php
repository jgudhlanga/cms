<?php

namespace App\Traits;

use App\Models\Scopes\Tenant\TenantScope;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (@Auth::user()->tenant_id && is_null($model->tenant_id)) {
                $model->tenant_id = @Auth::user()->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

}
