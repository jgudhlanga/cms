<?php

namespace App\Models\Scopes\Tenant;

use App\Enums\Shared\PermissionEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = @Auth::user();
		if ($user instanceof User && $user->tenant_id && ! ($user->can(PermissionEnum::ROOT_MANAGE))) {
            $builder->where($model->getTable().'.tenant_id', '=', @Auth::user()->tenant_id);
        }
    }
}
