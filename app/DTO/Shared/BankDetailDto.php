<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\BankDetailRequest;

readonly class BankDetailDto
{
	public function __construct(
		public string  $bank_id,
		public string  $bank_branch_id,
		public string  $bank_account_type_id,
		public string  $bank_account_holder,
		public ?string $bank_account_number,
		public ?bool   $bank_account_is_main,
	)
	{
	}

	public static function fromBankDetailRequest(BankDetailRequest $request): BankDetailDto
	{
		return new self(
			bank_id: $request->bank_id,
			bank_branch_id: $request->bank_branch_id,
			bank_account_type_id: $request->bank_account_type_id,
			bank_account_holder: $request->bank_account_holder,
			bank_account_number: $request->bank_account_number,
			bank_account_is_main: $request->bank_account_is_main ?? false,
		);
	}
}
