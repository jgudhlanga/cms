<?php

namespace App\Repositories\Finance;

use App\DTO\Finance\FinanceExchangeRateDto;
use App\Http\Filters\Finance\FinanceExchangeRateFilter;
use App\Models\Finance\FinanceExchangeRate;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Finance\interface\IFinanceExchangeRateRepository;

class FinanceExchangeRateRepository extends BaseRepository implements IFinanceExchangeRateRepository
{
    public function __construct(protected FinanceExchangeRate $exchangeRate)
    {
        parent::__construct($this->exchangeRate);
    }

    public function create(FinanceExchangeRateDto $dto): FinanceExchangeRate
    {
        return $this->exchangeRate->create([
            'date' => $dto->date,
            'currency_from' => $dto->currency_from,
            'currency_to' => $dto->currency_to,
            'rate' => $dto->rate,
        ])->refresh();
    }

    public function update(FinanceExchangeRate $exchangeRate, FinanceExchangeRateDto $dto): FinanceExchangeRate
    {
        return tap($exchangeRate)->update([
            'date' => $dto->date,
            'currency_from' => $dto->currency_from,
            'currency_to' => $dto->currency_to,
            'rate' => $dto->rate,
        ])->refresh();
    }

    public function allFilter($columns = ['*'], ?FinanceExchangeRateFilter $filters = null)
    {
        return $this->exchangeRate
            ->select($columns)
            ->filter($filters)
            ->orderByDesc('date')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}
