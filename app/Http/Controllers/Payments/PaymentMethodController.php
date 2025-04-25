<?php

namespace App\Http\Controllers\Payments;

use App\DTO\Payments\PaymentMethodDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Payments\PaymentMethodRequest;
use App\Http\Resources\Payments\PaymentMethodResource;
use App\Models\Payments\PaymentMethod;
use App\Repositories\Payments\interface\IPaymentMethodRepository;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
	public function __construct(protected IPaymentMethodRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewSettings');
		$paymentMethods = PaymentMethodResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('payments/paymentMethods/Index', [
			'paymentMethods' => $paymentMethods,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(PaymentMethodRequest $request)
	{
		$this->authorize('createSettings');
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
		$this->authorize('updateSettings');
		$this->repository->update($paymentMethod, PaymentMethodDto::fromPaymentMethodRequest($request));
	}

	public function destroy(PaymentMethod $paymentMethod)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($paymentMethod);
	}

	public function restore(string $id)
	{
		$paymentMethod = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($paymentMethod);
	}

	public function forceDelete(PaymentMethod $paymentMethod)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($paymentMethod, true);
	}
}
