<?php

namespace App\Repositories\Ledgers;

use App\DTO\Ledgers\LedgerDto;
use App\Http\Filters\Ledgers\LedgerFilter;
use App\Models\Ledgers\Ledger;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Ledgers\interface\ILedgerRepository;

class LedgerRepository extends BaseRepository implements ILedgerRepository
{
    public function __construct(protected Ledger $ledger)
    {
        parent::__construct($this->ledger);
    }

    public function create(LedgerDto $dto): Ledger
    {
        return $this->ledger->create($this->getFields($dto))->refresh();
    }

    public function update(Ledger $ledger, LedgerDto $dto): Ledger
    {
        return tap($ledger)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], LedgerFilter $filters = null)
    {
        return $this->ledger
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(LedgerDto $dto): array
    {
        return [
            'amount' => $dto->amount,
        ];
    }
}
