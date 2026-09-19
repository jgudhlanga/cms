<?php

declare(strict_types=1);

namespace App\Repositories\Institution;

use App\DTO\Documents\OfferLetterTemplateDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IOfferLetterTemplateRepository;

class OfferLetterTemplateRepository extends BaseRepository implements IOfferLetterTemplateRepository
{
    public function __construct(protected OfferLetterTemplate $offerLetterTemplate)
    {
        parent::__construct($this->offerLetterTemplate);
    }

    public function createForIntake(IntakePeriod $intakePeriod, OfferLetterTemplateDto $dto): OfferLetterTemplate
    {
        $template = $this->offerLetterTemplate->create($this->getFields($intakePeriod, $dto))->refresh();
        $this->syncScopes($template, $dto);

        return $template->fresh($this->relations()) ?? $template;
    }

    public function updateTemplate(OfferLetterTemplate $template, OfferLetterTemplateDto $dto): OfferLetterTemplate
    {
        $template = tap($template)->update($this->getFields($template->intakePeriod, $dto))->refresh();
        $this->syncScopes($template, $dto);

        return $template->fresh($this->relations()) ?? $template;
    }

    public function allForIntake(IntakePeriod $intakePeriod, $columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->offerLetterTemplate
            ->select($columns)
            ->where('intake_period_id', $intakePeriod->id)
            ->with($this->relations())
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @return list<string>
     */
    private function relations(): array
    {
        return [
            'intakePeriod',
            'institutionDepartments.department',
            'levels',
            'course',
            'modeOfStudy',
            'headerLogoOne',
            'headerLogoTwo',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getFields(IntakePeriod $intakePeriod, OfferLetterTemplateDto $dto): array
    {
        return [
            'tenant_id' => $intakePeriod->tenant_id,
            'intake_period_id' => $intakePeriod->id,
            'name' => $dto->name,
            'helper_description' => $dto->helper_description,
            'mode_of_study_id' => $dto->mode_of_study_id,
            'course_id' => $dto->course_id,
            'tuition_override' => $dto->tuition_override,
            'header_line_1' => $dto->header_line_1,
            'header_line_2' => $dto->header_line_2,
            'header_address_line_1' => $dto->header_address_line_1,
            'header_address_line_2' => $dto->header_address_line_2,
            'header_telephone' => $dto->header_telephone,
            'header_email' => $dto->header_email,
            'header_website' => $dto->header_website,
            'body' => $dto->body,
        ];
    }

    private function syncScopes(OfferLetterTemplate $template, OfferLetterTemplateDto $dto): void
    {
        $template->institutionDepartments()->sync($dto->institution_department_ids);
        $template->levels()->sync($dto->level_ids);
    }
}
