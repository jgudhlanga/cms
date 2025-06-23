<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Payments\PaymentFrequencyDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Requests\Shared\PaymentFrequencyRequest;
use App\Http\Resources\Shared\PaymentFrequencyResource;
use App\Models\Shared\PaymentFrequency;
use App\Repositories\Shared\interface\IPaymentFrequencyRepository;
use Inertia\Inertia;

class PaymentFrequencyController extends Controller
{
	public function __construct(protected IPaymentFrequencyRepository $repository)
	{
	}

	public function index(SharedTitleFilter $filters)
	{
		$this->authorize('viewSettings');
		$paymentFrequencies = PaymentFrequencyResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('payments/paymentFrequencies/Index', [
			'paymentFrequencies' => $paymentFrequencies,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(PaymentFrequencyRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(PaymentFrequencyDto::fromPaymentFrequencyRequest($request));
	}

	public function show(PaymentFrequency $paymentFrequency)
	{
		//
	}

	public function edit(PaymentFrequency $paymentFrequency)
	{
		//
	}

	public function update(PaymentFrequencyRequest $request, PaymentFrequency $paymentFrequency)
	{
		$this->authorize('updateSettings');
		$this->repository->update($paymentFrequency, PaymentFrequencyDto::fromPaymentFrequencyRequest($request));
	}

	public function destroy(PaymentFrequency $paymentFrequency)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($paymentFrequency);
	}

	public function restore(string $id)
	{
		$paymentFrequency = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($paymentFrequency);
	}

	public function forceDelete(PaymentFrequency $paymentFrequency)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($paymentFrequency, true);
	}
}
