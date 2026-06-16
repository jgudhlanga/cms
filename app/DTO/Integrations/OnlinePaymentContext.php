<?php

namespace App\DTO\Integrations;

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Shared\FeeType;
use Illuminate\Database\Eloquent\Model;

readonly class OnlinePaymentContext
{
    public function __construct(
        public FeeType $feeType,
        public FeeTypeEnum $feeTypeEnum,
        public Model $ledgerable,
        public IntakePeriod $intakePeriod,
        public ?int $studentProgramId = null,
    ) {}
}
