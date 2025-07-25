<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\IntakePeriodClassSizeRequest;

readonly class IntakePeriodClassSizeDto
{
    public function __construct(
        public int   $intake_period_id,
        public array $class_sizes,
    )
    {
    }

    public static function fromIntakePeriodClassSizeRequest(IntakePeriodClassSizeRequest $request): IntakePeriodClassSizeDto
    {
        return new self(
            intake_period_id: $request->intake_period_id,
            class_sizes: $request->class_sizes,
        );
    }
}
