<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\PaymentDayDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\PaymentDayRequest;
use App\Http\Resources\Shared\PaymentDayResource;
use App\Models\Shared\PaymentDay;
use App\Repositories\Shared\interface\IPaymentDayRepository;
use Inertia\Inertia;

class PaymentDayController extends Controller
{
	public function __construct(protected IPaymentDayRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewAny', PaymentDay::class);
		$paymentDays = PaymentDayResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/payments/paymentDays/Index', [
			'paymentDays' => $paymentDays,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', PaymentDay::class);
	}

	public function store(PaymentDayRequest $request)
	{
		$this->authorize('create', PaymentDay::class);
		$this->repository->create(PaymentDayDto::fromPaymentDayRequest($request));
	}

	public function show(PaymentDay $paymentDay)
	{
		//
	}

	public function edit(PaymentDay $paymentDay)
	{
		//
	}

	public function update(PaymentDayRequest $request, PaymentDay $paymentDay)
	{
		$this->authorize('update', $paymentDay);
		$this->repository->update($paymentDay, PaymentDayDto::fromPaymentDayRequest($request));
	}

	public function destroy(PaymentDay $paymentDay)
	{
		$this->authorize('delete', $paymentDay);
		$this->repository->delete($paymentDay);
	}

	public function restore(string $id)
	{
		$paymentDay = $this->repository->findTrashed($id);
		$this->authorize('restore', $paymentDay);
		$this->repository->restore($paymentDay);
	}

	public function forceDelete(PaymentDay $paymentDay)
	{
		$this->authorize('forceDelete', $paymentDay);
		$this->repository->delete($paymentDay, true);
	}
}
