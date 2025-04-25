<?php

namespace App\Repositories\Payments\interface;

use App\DTO\Payments\PaymentFrequencyDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Payments\PaymentFrequency;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPaymentFrequencyRepository extends IBaseRepository
{
    public function create(PaymentFrequencyDto $dto);

    public function update(PaymentFrequency $paymentFrequency, PaymentFrequencyDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
