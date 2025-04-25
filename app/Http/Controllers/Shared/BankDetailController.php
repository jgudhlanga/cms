<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\BankDetailDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\BankDetailRequest;
use App\Models\Shared\BankDetail;
use App\Repositories\Shared\interface\IBankDetailRepository;

class BankDetailController extends Controller
{
	public function __construct(protected IBankDetailRepository $repository)
	{
	}


	public function update(BankDetailRequest $request, BankDetail $bankDetail)
	{
		$this->authorize('create', $bankDetail);
		$this->repository->update($bankDetail, BankDetailDto::fromBankDetailRequest($request));
	}

	public function destroy(BankDetail $bankDetail)
	{
		$this->authorize('delete', $bankDetail);
		$this->repository->delete($bankDetail);
	}

	public function restore(string $id)
	{
		$bankDetail = $this->repository->findTrashed($id);
		$this->authorize('restore', $bankDetail);
		$this->repository->restore($bankDetail);
	}

	public function forceDelete(BankDetail $bankDetail)
	{
		$this->authorize('forceDelete', $bankDetail);
		$this->repository->delete($bankDetail, true);
	}
}
