<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Payments\PaymentDayDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\PaymentDay;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPaymentDayRepository extends IBaseRepository
{
    public function create(PaymentDayDto $dto);

    public function update(PaymentDay $paymentDay, PaymentDayDto $dto);

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
