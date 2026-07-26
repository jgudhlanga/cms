<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\PaymentMethodDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\PaymentMethodRequest;
use App\Http\Resources\Shared\PaymentMethodResource;
use App\Models\Shared\PaymentMethod;
use App\Repositories\Shared\interface\IPaymentMethodRepository;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
	public function __construct(protected IPaymentMethodRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewAny', PaymentMethod::class);
		$paymentMethods = PaymentMethodResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/payments/paymentMethods/Index', [
			'paymentMethods' => $paymentMethods,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', PaymentMethod::class);
	}

	public function store(PaymentMethodRequest $request)
	{
		$this->authorize('create', PaymentMethod::class);
		$this->repository->create(PaymentMethodDto::fromPaymentMethodRequest($request));
	}

	public function show(PaymentMethod $paymentMethod)
	{
		//
	}

	public function edit(PaymentMethod $paymentMethod)
	{
		//
	}

	public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod)
	{
		$this->authorize('update', $paymentMethod);
		$this->repository->update($paymentMethod, PaymentMethodDto::fromPaymentMethodRequest($request));
	}

	public function destroy(PaymentMethod $paymentMethod)
	{
		$this->authorize('delete', $paymentMethod);
		$this->repository->delete($paymentMethod);
	}

	public function restore(string $id)
	{
		$paymentMethod = $this->repository->findTrashed($id);
		$this->authorize('restore', $paymentMethod);
		$this->repository->restore($paymentMethod);
	}

	public function forceDelete(PaymentMethod $paymentMethod)
	{
		$this->authorize('forceDelete', $paymentMethod);
		$this->repository->delete($paymentMethod, true);
	}
}
