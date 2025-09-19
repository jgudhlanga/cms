<?php

namespace App\DTO\Ledgers;

use App\Http\Requests\Ledgers\LedgerRequest;
use App\Http\Requests\Users\UserRequest;
use App\Models\Shared\Status;
use App\Models\Tenants\Tenant;

readonly class LedgerDto
{
    public function __construct(
        public string  $amount,
    )
    {
    }


    public static function fromLedgerRequest(LedgerRequest $request): LedgerDto
    {
        return new self(
            amount: $request->amount,
        );
    }
}
