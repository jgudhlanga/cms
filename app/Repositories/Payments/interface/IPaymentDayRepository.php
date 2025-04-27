<?php

namespace App\Repositories\Payments\interface;

use App\DTO\Payments\PaymentDayDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Payments\PaymentDay;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPaymentDayRepository extends IBaseRepository
{
    public function create(PaymentDayDto $dto);

    public function update(PaymentDay $paymentDay, PaymentDayDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
