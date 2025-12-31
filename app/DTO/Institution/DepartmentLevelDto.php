<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentLevelRequest;

readonly class DepartmentLevelDto
{
    public function __construct(
        public array $level_ids,
        public array $show_on_current_application_period,
    )
    {
    }


    public static function fromDepartmentLevelRequest(DepartmentLevelRequest $request): DepartmentLevelDto
    {
        return new self(
            level_ids: $request->level_ids,
            show_on_current_application_period: $request->show_on_current_application_period,
        );
    }
}
