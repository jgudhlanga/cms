<?php

namespace App\Repositories\Payments;

use App\DTO\Payments\PaymentMethodDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Payments\PaymentMethod;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Payments\interface\IPaymentMethodRepository;

class PaymentMethodRepository extends BaseRepository implements IPaymentMethodRepository
{
    public function __construct(protected PaymentMethod $paymentMethod)
    {
        parent::__construct($this->paymentMethod);
    }

    public function create(PaymentMethodDto $dto): PaymentMethod
    {
        return $this->paymentMethod->create([
			'title' => $dto->title,
			'description' => $dto->description,
        ])->refresh();
    }

    public function update(PaymentMethod $paymentMethod, PaymentMethodDto $dto): PaymentMethod
    {
        return tap($paymentMethod)->update([
			'title' => $dto->title,
			'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
    {
        return $this->paymentMethod
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
    }
}
