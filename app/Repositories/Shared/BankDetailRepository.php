<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\BankDetailDto;
use App\Helpers\Helper;
use App\Models\Shared\BankDetail;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IBankDetailRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BankDetailRepository extends BaseRepository implements IBankDetailRepository
{
	public function __construct(protected BankDetail $bankDetail)
	{
		parent::__construct($this->bankDetail);
	}

	public function create(Model $model, BankDetailDto $dto): BankDetail
	{
		$this->handleMainBankDetail($dto);
		return BankDetail::create(
			array_merge(
				[
					'tenant_id' => $model->tenant_id ?? @Auth::user()->tenant_id,
					'bankable_id' => $model->id,
					'bankable_type' => get_class($model)
				],
				$this->getFields($dto))
		);
	}

	public function update(BankDetail $bankDetail, BankDetailDto $dto): BankDetail
	{
		$this->handleMainBankDetail($dto);
		return tap($bankDetail)->update($this->getFields($dto));
	}

	private function getFields(BankDetailDto $dto): array
	{
		return [
			'bank_id' => $dto->bank_id,
			'bank_branch_id' => $dto->bank_branch_id,
			'bank_account_type_id' => $dto->bank_account_type_id,
			'bank_account_holder' => $dto->bank_account_holder,
			'bank_account_number' => Helper::encrypt($dto->bank_account_number),
			'bank_account_is_main' => $dto->bank_account_is_main ?? false,
		];
	}

	private function handleMainBankDetail(BankDetailDto $dto): void
	{
		if ($dto->bank_account_is_main) {
			BankDetail::query()->update(['bank_account_is_main' => false]);
		}
	}
}
