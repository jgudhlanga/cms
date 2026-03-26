<?php

namespace App\Repositories\Finance\interface;

use App\DTO\Finance\FinanceExchangeRateDto;
use App\Http\Filters\Finance\FinanceExchangeRateFilter;
use App\Models\Finance\FinanceExchangeRate;
use App\Repositories\Base\Interface\IBaseRepository;

interface IFinanceExchangeRateRepository extends IBaseRepository
{
    public function create(FinanceExchangeRateDto $dto): FinanceExchangeRate;

    public function update(FinanceExchangeRate $exchangeRate, FinanceExchangeRateDto $dto): FinanceExchangeRate;

    public function allFilter($columns = ['*'], ?FinanceExchangeRateFilter $filters = null);
}
