<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\FeeStructureRequest;

readonly class FeeStructureDto
{
    public function __construct(
        public string  $fee_type_id,
        public ?string $level_id,
        public ?string $mode_of_study_id,
        public ?float  $amount,
        public ?float  $local_fca_amount
    )
    {
    }


    public static function fromFeeStructureRequest(FeeStructureRequest $request): FeeStructureDto
    {
        return new self(
            fee_type_id: $request->fee_type_id,
            level_id: $request->level_id,
            mode_of_study_id: $request->mode_of_study_id,
            amount: $request->amount,
            local_fca_amount: $request->local_fca_amount
        );
    }
}
