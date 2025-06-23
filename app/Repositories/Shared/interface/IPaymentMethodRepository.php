<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Payments\PaymentMethodDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\PaymentMethod;
use App\Repositories\Base\Interface\IBaseRepository;

interface IPaymentMethodRepository extends IBaseRepository
{
	public function create(PaymentMethodDto $dto);

	public function update(PaymentMethod $paymentMethod, PaymentMethodDto $dto);

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null);
}
