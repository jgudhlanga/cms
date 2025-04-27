<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\BankDetailDto;
use App\Models\Shared\BankDetail;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Model;

interface IBankDetailRepository extends IBaseRepository
{
	public function create(Model $model, BankDetailDto $dto);

	public function update(BankDetail $bankDetail, BankDetailDto $dto);

}
