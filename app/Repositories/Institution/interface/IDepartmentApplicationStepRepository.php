<?php

namespace App\Repositories\Institution\interface;

use App\DTO\Institution\DepartmentApplicationStepDto;
use App\DTO\Institution\DepartmentApplicationStepUpdateDto;
use App\DTO\Institution\WorkflowStepActionMetadataDto;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDepartmentApplicationStepRepository extends IBaseRepository
{
    public function syncDepartmentApplicationSteps(InstitutionDepartment $institutionDepartment, DepartmentApplicationStepDto $dto);
    public function syncWorkflowStepActionMetadata(WorkflowStepActionMetadataDto $dto);

    public function update(DepartmentApplicationStep $departmentApplicationStep, DepartmentApplicationStepUpdateDto $dto);

}
