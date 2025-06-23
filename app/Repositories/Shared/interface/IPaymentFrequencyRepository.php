<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\PaymentFrequencyDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\PaymentFrequency;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPaymentFrequencyRepository extends IBaseRepository
{
    public function create(PaymentFrequencyDto $dto);

    public function update(PaymentFrequency $paymentFrequency, PaymentFrequencyDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
