<?php

namespace App\DTO\Tenants;

use App\Http\Requests\Tenants\TenantRequest;

class TenantDto
{
    public function __construct(
        public readonly? string $name,
        public readonly? string $meta,

    )
    {
    }


    public static function fromTenantRequest(TenantRequest $request): TenantDto
    {
        return new self(
            name: $request->name,
            meta: $request->meta,
        );
    }
}
