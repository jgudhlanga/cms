<?php

namespace App\Repositories\Ledgers\interface;

use App\DTO\Ledgers\LedgerDto;

use App\Http\Filters\Ledgers\LedgerFilter;
use App\Models\Ledgers\Ledger;
use App\Repositories\Base\Interface\IBaseRepository;

interface ILedgerRepository extends IBaseRepository
{
    public function create(LedgerDto $dto);

    public function update(Ledger $ledger, LedgerDto $dto);

    public function allFilter($columns = ['*'], LedgerFilter $filters = null);
}
