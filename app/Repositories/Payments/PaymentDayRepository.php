<?php

namespace App\Repositories\Payments;

use App\DTO\Payments\PaymentDayDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Payments\PaymentDay;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Payments\interface\IPaymentDayRepository;

class PaymentDayRepository extends BaseRepository implements IPaymentDayRepository
{
    public function __construct(protected PaymentDay $paymentDay)
    {
        parent::__construct($this->paymentDay);
    }

    public function create(PaymentDayDto $dto): PaymentDay
    {
        return $this->paymentDay->create([
			'title' => $dto->title,
			'description' => $dto->description,
        ])->refresh();
    }

    public function update(PaymentDay $paymentDay, PaymentDayDto $dto): PaymentDay
    {
        return tap($paymentDay)->update([
			'title' => $dto->title,
			'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
    {
        return $this->paymentDay
			->select($columns)
			->filter($filters)
			->orderBy('created_at')
			->orderBy('title')
			->paginate()
			->withQueryString();
    }
}
