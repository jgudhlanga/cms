<?php

namespace App\Http\Controllers\Finance;

use App\DTO\Finance\FinanceExchangeRateDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Finance\FinanceExchangeRateFilter;
use App\Http\Requests\Finance\FinanceExchangeRateRequest;
use App\Http\Resources\Finance\FinanceExchangeRateResource;
use App\Models\Finance\FinanceExchangeRate;
use App\Repositories\Finance\interface\IFinanceExchangeRateRepository;
use Inertia\Inertia;

class FinanceExchangeController extends Controller
{
    public function __construct(protected IFinanceExchangeRateRepository $repository) {}

    public function index(FinanceExchangeRateFilter $filters)
    {
        $this->authorize('viewFinanceSettings');

        $exchangeRates = FinanceExchangeRateResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('finance/FinanceExchangeRates/Index', [
            'exchangeRates' => $exchangeRates,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function store(FinanceExchangeRateRequest $request): void
    {
        $this->authorize('createFinanceSettings');

        $this->repository->create(FinanceExchangeRateDto::fromFinanceExchangeRateRequest($request));
    }

    public function update(FinanceExchangeRateRequest $request, FinanceExchangeRate $exchangeRate): void
    {
        $this->authorize('updateFinanceSettings');

        $this->repository->update($exchangeRate, FinanceExchangeRateDto::fromFinanceExchangeRateRequest($request));
    }

    public function destroy(FinanceExchangeRate $exchangeRate): void
    {
        $this->authorize('deleteFinanceSettings');

        $this->repository->delete($exchangeRate);
    }

    public function restore(string $id): void
    {
        $this->authorize('restoreFinanceSettings');

        $exchangeRate = $this->repository->findTrashed($id);

        $this->repository->restore($exchangeRate);
    }

    public function forceDelete(FinanceExchangeRate $exchangeRate): void
    {
        $this->authorize('forceDeleteFinanceSettings');

        $this->repository->delete($exchangeRate, true);
    }
}
