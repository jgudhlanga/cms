<?php

namespace App\Http\Resources\Shared;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
			'type' => 'bank-detail',
			'id' => $this->id,
			'attributes' => [
				'bankId' => $this->bank_id,
				'bank' => $this->bank?->name,
				'bankBranchId' => $this->bank_branch_id,
				'bankBranch' => $this->bankBranch?->name,
				'bankBranchCode' => $this->bankBranch?->code,
				'bankAccountTypeId' => $this->bank_account_type_id,
				'bankAccountType' => $this->bankAccountType?->title,
				'bankAccountHolder' => $this->bank_account_holder,
				'bankAccountNumber' => Helper::mask(Helper::decrypt($this->bank_account_number)),
				'bankAccountNumberDecrypted' => Helper::decrypt($this->bank_account_number),
				'bankAccountIsMain' => $this->bank_account_is_main,
				'createdAt' => $this->created_at,
				'updatedAt' => $this->updated_at,
				'deletedAt' => $this->deleted_at,
			]
		];
    }
}
