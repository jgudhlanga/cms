<?php

declare(strict_types=1);

namespace App\Http\Resources\Documents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferLetterTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $departments = $this->institutionDepartments
            ? $this->institutionDepartments->map(fn ($department): array => [
                'id' => (int) $department->id,
                'name' => (string) ($department->department?->name ?? ''),
                'code' => (string) ($department->department_code ?? ''),
            ])->values()->all()
            : [];

        $levels = $this->levels
            ? $this->levels->map(fn ($level): array => [
                'id' => (int) $level->id,
                'name' => (string) $level->name,
            ])->values()->all()
            : [];

        return [
            'type' => 'offer-letter-template',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'helperDescription' => $this->helper_description,
                'intakePeriodId' => $this->intake_period_id,
                'intakePeriod' => $this->intakePeriod?->name ?? null,
                'institutionDepartmentIds' => collect($departments)->pluck('id')->all(),
                'departments' => collect($departments)
                    ->map(fn (array $department): string => $department['code'] !== ''
                        ? $department['code']
                        : $department['name'])
                    ->filter()
                    ->implode(', '),
                'departmentOptions' => $departments,
                'levelIds' => collect($levels)->pluck('id')->all(),
                'levels' => collect($levels)->pluck('name')->filter()->implode(', '),
                'levelOptions' => $levels,
                'modeOfStudyId' => $this->mode_of_study_id,
                'modeOfStudy' => $this->modeOfStudy?->name ?? null,
                'courseId' => $this->course_id,
                'course' => $this->course?->name ?? null,
                'tuitionOverride' => $this->tuition_override,
                'headerLine1' => $this->header_line_1,
                'headerLine2' => $this->header_line_2,
                'headerAddressLine1' => $this->header_address_line_1,
                'headerAddressLine2' => $this->header_address_line_2,
                'headerTelephone' => $this->header_telephone,
                'headerEmail' => $this->header_email,
                'headerWebsite' => $this->header_website,
                'headerLogoOne' => $this->header_logo_1,
                'headerLogoOneUrl' => ($this->header_logo_1 ?? 0) > 0
                    ? $this->headerLogoOne?->getFullUrl()
                    : null,
                'headerLogo2' => $this->header_logo_2,
                'headerLogo2Url' => ($this->header_logo_2 ?? 0) > 0
                    ? $this->headerLogoTwo?->getFullUrl()
                    : null,
                'body' => $this->body,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
        ];
    }
}
