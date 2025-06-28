<?php

namespace App\Repositories\Shared;

use App\DTO\Shared\PaymentFrequencyDto;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Models\Shared\PaymentFrequency;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IPaymentFrequencyRepository;

class PaymentFrequencyRepository extends BaseRepository implements IPaymentFrequencyRepository
{
	public function __construct(protected PaymentFrequency $paymentFrequency)
	{
		parent::__construct($this->paymentFrequency);
	}

	public function create(PaymentFrequencyDto $dto): PaymentFrequency
	{
		return $this->paymentFrequency->create([
			'title' => $dto->title,
			'description' => $dto->description,
		])->refresh();
	}

	public function update(PaymentFrequency $paymentFrequency, PaymentFrequencyDto $dto): PaymentFrequency
	{
		return tap($paymentFrequency)->update([
			'title' => $dto->title,
			'description' => $dto->description,
		]);
	}

	public function allFilter($columns = ['*'], SharedTitleFilter $filters = null)
	{
		return $this->paymentFrequency
			->select($columns)
			->filter($filters)
			->orderBy('title')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
